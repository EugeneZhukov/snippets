<?php

$str = '';
$userMessage = '';
if(isset( $_POST['message'])){
    $userMessage = $_POST['message'];
    $strlenResult  = strlen($userMessage)-1;

    for ($i = 0; $i < $strlenResult/2; $i++) {
        $str = $userMessage[$i];
        $userMessage[$i] = $userMessage[$strlenResult-$i];
        $userMessage[$strlenResult-$i] = $str;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <style>
        *{
            margin: 0;
            padding: 0;
            outline: none;
            border: none;
            box-sizing: border-box;
        }
        body {
            background: #34495E;
        }
        .wrapper{
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 10rem auto;
            color: #fff;
        }
        .wrapper p{
            font-size: 2em;
        }
        .wrapper input{
            font-size: 1em;
            padding: 5px 10px;
            margin-top: 10px;
            border-radius: 2px;
        }
        .wrapper button{
            background: #3498db;
            color: #fff;
            text-transform: uppercase;
            font-size: 1em;
            letter-spacing: 0.2em;
            margin-top: 10px;
            padding: 10px;
            cursor: pointer;
            border-radius: 2px;
        }
        .wrapper button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <form action="strReverse.php" method="post">
        <div class="form-group">
            <label for="message"><?php echo $userMessage; ?></label>
            <input type="text" class="form-control" id="message" name="message" placeholder="Enter message">
        </div>
        <button type="submit" class="btn btn-primary">Reverse message</button>
    </form>
</div>
</body>
</html>