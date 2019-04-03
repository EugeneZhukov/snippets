<?php

namespace BKontor\GatewayBundle\ActionHandler\Internal;

use Kontor\GatewayBundle\ActionHandler\AbstractActionHandler;
use BKontor\BaseBundle\Entity\PaymentProcessor;
use BKontor\BaseBundle\Entity\Currency;
use BKontor\BaseBundle\Entity\SkrillDeposit;


class StartSkrillHandler extends AbstractActionHandler
{
    /** @var \Doctrine\ORM\EntityManager */
    protected $em;

    /** @var \BKontor\BaseBundle\Entity\Currency */
    protected $currency;

    /** @var $logger LoggerInterface */
    protected $logger;

    public function validate()
    {
        $this->em = $this->container->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('logger');

        $skrillProcessor = $this->em->getRepository('BKontorBaseBundle:PaymentProcessor')->findOneBy([
            'name' => 'skrill '
        ]);

        if(!$skrillProcessor instanceof PaymentProcessor){
            $this->addError('SKRILL_UNKNOWN');

            return false;
        }

        if(!$skrillProcessor->isActivated()){
            $this->addError('SKRILL_INACTIVE');

            return false;
        }

        /** @var $licenseService \BKontor\BaseBundle\Service\LicenseService */
        $licenseService = $this->getContainer()->get('bkontor.license');

        if(PaymentProcessor::PRODUCTION == $skrillProcessor->getState() && !$licenseService->hasOption('skrill')){
            $this->addError('SKRILL_PRODUCTION_UNLICENSED');

            return false;
        }

        $this->currency = $this->em->getRepository('BKontorBaseBundle:Currency')->find($this->getOption('currency'));

        if(!$this->currency instanceof Currency || !$this->getLicenseService()->isCurrencyLicensed($this->currency)){
            $this->addError('CURRENCY_UNKNOWN');

            return false;
        }

        if(!$skrillProcessor->containsCurrency($this->currency)){
            $this->addError('THIS_CURRENCY_DEPOSITS_NOT_SUPPORTED_BY_SKRILL');

            return false;
        }

        $account = $this->em->getRepository('BKontorBaseBundle:Account')->findOneBy(
            array('user' => $this->getUser()->getId(), 'currency' => $this->getOption('currency'))
        );

        /**
         * Is the requested withdrawal amount above the minimum setting?
         * @var $valConvService ValueConversionService
         * */
        $valConvService = $this->container->get('bkontor.value_conversion');
        $limitBalancesService = $this->container->get('draglet.limit_balances');
        $fxService = $this->container->get('bkontor.fx');

        $limitBalancesService->setUser($this->user);

        $totalBalances = $limitBalancesService->calculateLimitBalances("total", 'limitFD');
        $userBalances = $limitBalancesService->calculateLimitBalances("user", 'limitFD');
        $currenciesBalances = $limitBalancesService->calculateLimitBalances("currency", 'limitFD');

        $this->logger->info('limit send funds', array(
            '$totalBalancies' => $totalBalances,
            '$userBalancies' => $userBalances,
            '$currenciesBalancies' => $currenciesBalances
        ));

        if ($totalBalances){
            foreach ($totalBalances as $balance){
                $this->logger->info('total balance', array(
                    'type' => $balance[0],
                    'time' => $balance[1],
                    'period' => $balance[2],
                    'currencyType' => $balance[3],
                    'currencyID' => $balance[4],
                    'balance' => $balance[5],
                    'limit' => $balance[6],
                ));

                $limitCurrency = $this->em->getRepository('BKontorBaseBundle:Currency')->find($balance[4]);
                $convertedAmount = $fxService->convertValue($this->currency, $limitCurrency, $valConvService->toInternal($this->getOption('amount')));

                if (($balance[5] - $convertedAmount) < 0){
                    $this->addError('DEPOSIT_LIMIT', ['total limit' => $balance[6], 'balance' => $balance[5], 'request amount' => $this->getOption('amount')]);

                    return false;
                }
            }
        }

        if ($userBalances){
            foreach ($userBalances as $balance){
                $this->logger->info('user balance', array(
                    'type' => $balance[0],
                    'time' => $balance[1],
                    'period' => $balance[2],
                    'currencyType' => $balance[3],
                    'currencyID' => $balance[4],
                    'balance' => $balance[5],
                    'limit' => $balance[6],
                ));

                $limitCurrency = $this->em->getRepository('BKontorBaseBundle:Currency')->find($balance[4]);
                $convertedAmount = $fxService->convertValue($this->currency, $limitCurrency, $valConvService->toInternal($this->getOption('amount')));

                if (($balance[5] - $convertedAmount) < 0){
                    $this->addError('DEPOSIT_LIMIT', ['user limit' => $balance[6], 'balance' => $balance[5], 'request amount' => $this->getOption('amount')]);

                    return false;
                }
            }
        }

        if ($currenciesBalances){
            foreach ($currenciesBalances as $balance){
                $this->logger->info('currency balance', array(
                    'type' => $balance[0],
                    'time' => $balance[1],
                    'period' => $balance[2],
                    'currencyType' => $balance[3],
                    'currencyID' => $balance[4],
                    'balance' => $balance[5],
                    'limit' => $balance[6],
                ));

                $limitCurrency = $this->em->getRepository('BKontorBaseBundle:Currency')->find($balance[4]);
                $convertedAmount = $fxService->convertValue($this->currency, $limitCurrency, $valConvService->toInternal($this->getOption('amount')));

                if (($balance[5] - $convertedAmount) < 0){
                    $this->addError('DEPOSIT_LIMIT', ['currency limit' => $balance[6], 'balance' => $balance[5], 'request amount' => $this->getOption('amount')]);

                    return false;
                }
            }
        }

        if(!$account){
            $this->addError('NO_USER_ACCOUNT_IN_THAT_CURRENCY');
            $this->logger->debug(sprintf('The customer with id "%u" does not have the "%s" account', $this->getUser()->getId(), $this->getOption('currency')));

            return false;
        }

        return true;
    }

    public function handle()
    {
        /** @var $encrypter \BKontor\BaseBundle\Service\EncrypterService */
        $encrypter = $this->container->get('bkontor.encrypter');
        $valConvServ         = $this->getContainer()->get('bkontor.value_conversion');
        $amount              = $this->getOption('amount');
        $locale              = $this->getOption('locale');
        $ppSettingsService   = $this->getContainer()->get('bkontor.payment_processor_settings');
        $payURL              = $ppSettingsService->getValue('skrill', 'PayURL');
        $payToEmail          = $ppSettingsService->getValue('skrill', 'PayToEmail'.$this->getOption('currency'));

        if($payToEmail == null){
            $payToEmail      = $ppSettingsService->getValue('skrill', 'PayToEmail');
            $this->logger->info(sprintf('PayToEmail used "%u" ', $payToEmail));
        }

        $depositReturnURL    = $ppSettingsService->getValue('skrill', 'DepositReturnURL');
        $depositStatusURL    = $ppSettingsService->getValue('skrill', 'DepositStatusURL');

        $this->currency = $this->em->getRepository('BKontorBaseBundle:Currency')->find($this->getOption('currency'));
        $user = $this->getUser();

        try{
            /** @var $skrillDeposit \BKontor\BaseBundle\Entity\SkrillDeposit  */
            $skrillDeposit = new SkrillDeposit;
            $skrillDeposit
                ->setUser($this->getUser())
                ->setAmount($valConvServ->toInternal($amount))
                ->setCurrency($this->currency)
                ->setConfirmed(false)
            ;

            $this->em->persist($skrillDeposit);
            $this->em->flush($skrillDeposit);

            $this->logger->info(sprintf('New skrill transaction is created in DB with id "%u" for the user "%u"', $skrillDeposit->getId(), $user->getId()));

            $tokenText = sprintf('%u,%u,%u', time(), $this->getUser()->getId(), $skrillDeposit->getId());
            $token = $encrypter->encrypt($tokenText, SkrillDeposit::TOKEN_SECRET);

            $data = [
                "pay_to_email"    => $payToEmail,
                "amount"          => $amount,
                "currency"        => $this->currency->getId(),
                "transaction_id"  => $token,
                "language"        => $locale,
                "prepare_only"    => 1,
                "return_url"      => $depositReturnURL,
                "status_url"      => $depositStatusURL
            ];

            $this->logger->info('SKRILL_START_DEPOSIT', $data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $payURL );
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            // REDIRECT USER TO redirectUrl
            $this->getClientResponse()->setResults(array("redirectUrl" => $payURL.'/app/?sid'.'='.$response));

            return;
        }
        catch (\Exception $ex){

            $this->addError("SKRILL_GENERAL_EXCEPTION");

            $this->logger->notice('SKRILL_GENERAL_EXCEPTION', [
                "errorMessage" => $ex->getMessage()
            ]);
        }

    }
}
