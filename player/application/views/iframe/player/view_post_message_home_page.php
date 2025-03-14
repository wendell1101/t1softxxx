<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slot Machine</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
            font-family: Arial, sans-serif;
        }
        .slot-machine-container {
            position: relative;
            width: 260px;
        }
        .slot-machine {
            width: 220px;
            border: 5px solid #333;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        .reels {
            display: flex;
            justify-content: space-between;
            padding: 10px;
        }
        .reel {
            width: 50px;
            height: 70px;
            border: 2px solid #333;
            border-radius: 5px;
            background-color: #eee;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .reel span {
            font-size: 24px;
            line-height: 70px;
        }
        .button {
            padding: 10px;
            background-color: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px;
        }
        .button:hover {
            background-color: #45a049;
        }
        .home-button {
            position: absolute;
            top: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 18px;
            line-height: 30px;
            text-align: center;
            cursor: pointer;
        }
        .home-button:hover {
            background-color: #0056b3;
        }
        .home-button:first-of-type {
            right: 50px;
        }
        .home-button:last-of-type {
            right: 10px;
        }
    </style>
</head>
<body>
    <div class="slot-machine-container">
        <button class="home-button" onclick="window.location.href='<?= $redirect_url ?>'">🏠</button>
        <button class="home-button" onclick="window.parent.location.href='<?= $redirect_url ?>?return_previous_url=true'">🏠</button>
        <div class="slot-machine">
            <div class="reels">
                <div class="reel">
                    <span>@</span>
                    <span>@</span>
                    <span>@</span>
                </div>
                <div class="reel">
                    <span>@</span>
                    <span>@</span>
                    <span>@</span>
                </div>
                <div class="reel">
                    <span>@</span>
                    <span>@</span>
                    <span>@</span>
                </div>
            </div>
            <button class="button">SPIN</button>
        </div>
    </div>
</body>
</html>
