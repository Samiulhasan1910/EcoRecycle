<!DOCTYPE html>
<html>

<head>

<title>Login - EcoCycle</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #f4f8f5;
}

.navbar {
    height: 70px;
    background: white;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 7%;

    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.logo {
    font-size: 25px;
    font-weight: bold;
    color: #168a45;
}

.logo span {
    color: #222;
}

.navbar a {
    color: #168a45;
    text-decoration: none;
    font-weight: bold;
}

.main {
    min-height: calc(100vh - 70px);

    display: flex;
    justify-content: center;
    align-items: center;

    padding: 30px;
}

.login-box {
    width: 850px;
    max-width: 100%;

    background: white;

    border-radius: 18px;

    overflow: hidden;

    display: grid;
    grid-template-columns: 45% 55%;

    box-shadow: 0 8px 35px rgba(0,0,0,0.08);
}


/* LEFT */

.left {
    background: #168a45;
    color: white;

    padding: 45px 35px;
}

.left h2 {
    font-size: 30px;
    margin-bottom: 15px;
}

.left p {
    line-height: 1.7;
    margin-bottom: 25px;
    color: #e8fff0;
}

.benefit {
    margin: 15px 0;
}


/* RIGHT */

.right {
    padding: 45px;
}

.right h2 {
    margin-bottom: 8px;
}

.subtitle {
    color: #777;
    font-size: 14px;
    margin-bottom: 25px;
}

.group {
    margin-bottom: 20px;
}

label {
    display: block;

    font-size: 14px;
    font-weight: bold;

    margin-bottom: 7px;
}

input[type="email"],
input[type="password"] {
    width: 100%;

    padding: 12px;

    border: 1px solid #ddd;

    border-radius: 8px;

    font-size: 14px;
}

input:focus {
    outline: none;

    border-color: #168a45;
}


/* FORGOT PASSWORD */

.forgot-password {
    text-align: right;

    margin-top: -8px;

    margin-bottom: 20px;
}

.forgot-password a {
    color: #168a45;

    text-decoration: none;

    font-size: 13px;

    font-weight: bold;
}

.forgot-password a:hover {
    text-decoration: underline;
}


/* REMEMBER */

.remember {
    font-size: 13px;

    margin-bottom: 20px;
}

.remember input {
    width: auto;
    margin-right: 5px;
}


/* LOGIN BUTTON */

.login-btn {
    width: 100%;

    padding: 13px;

    border: none;

    border-radius: 8px;

    background: #168a45;

    color: white;

    font-weight: bold;

    cursor: pointer;
}

.login-btn:hover {
    background: #117239;
}


/* ERROR */

.error {
    color: #d93025;

    font-size: 12px;
}


/* MESSAGE */

.message {
    color: #d93025;

    font-size: 13px;

    margin-bottom: 15px;
}


/* REGISTER */

.register {
    text-align: center;

    margin-top: 18px;

    font-size: 14px;

    color: #777;
}

.register a {
    color: #168a45;

    font-weight: bold;

    text-decoration: none;
}


/* MOBILE */

@media(max-width:700px) {

    .login-box {
        grid-template-columns: 1fr;
    }

    .left {
        display: none;
    }

    .right {
        padding: 30px 22px;
    }

}

</style>

</head>


<body>


<!-- NAVBAR -->

<nav class="navbar">

    <div class="logo">

        Eco<span>Cycle</span>

    </div>


    <a href="index.php?url=auth/register">

        Register

    </a>

</nav>



<!-- MAIN -->

<div class="main">


    <div class="login-box">


        <!-- LEFT -->

        <div class="left">

            <h2>
                Welcome Back
            </h2>


            <p>

                Login to EcoCycle and continue
                buying, selling, donating and
                recycling within your community.

            </p>


            <div class="benefit">
                ✓ Buy useful items
            </div>


            <div class="benefit">
                ✓ Sell unused items
            </div>


            <div class="benefit">
                ✓ Donate items
            </div>


            <div class="benefit">
                ✓ Support sustainable living
            </div>

        </div>



        <!-- RIGHT -->

        <div class="right">


            <h2>
                Login
            </h2>


            <p class="subtitle">

                Enter your account details to continue.

            </p>


            <div
                id="message"
                class="message"
            ></div>



            <form id="loginForm">


                <!-- EMAIL -->

                <div class="group">

                    <label>
                        Email Address
                    </label>


                    <input
                        type="email"
                        id="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="example@email.com"
                    >


                    <span
                        id="emailError"
                        class="error"
                    ></span>

                </div>



                <!-- PASSWORD -->

                <div class="group">

                    <label>
                        Password
                    </label>


                    <input
                        type="password"
                        id="password"
                        placeholder="Enter your password"
                    >


                    <span
                        id="passwordError"
                        class="error"
                    ></span>

                </div>



                <!-- FORGOT PASSWORD -->

                <div class="forgot-password">

                    <a href="index.php?url=auth/forgot">

                        Forgot Password?

                    </a>

                </div>



                <!-- REMEMBER -->

                <div class="remember">

                    <input
                        type="checkbox"
                        id="remember"

                        <?php

                        if(isset($_COOKIE["remember_email"]))
                        {
                            echo "checked";
                        }

                        ?>

                    >

                    Remember my email

                </div>



                <!-- LOGIN BUTTON -->

                <button
                    type="submit"
                    class="login-btn"
                    id="loginButton"
                >

                    Login

                </button>


            </form>



            <!-- REGISTER -->

            <div class="register">

                Don't have an account?

                <a href="index.php?url=auth/register">

                    Register

                </a>

            </div>


        </div>

    </div>

</div>



<script>


document
.getElementById("loginForm")
.addEventListener(
    "submit",
    function(e)
    {

        e.preventDefault();


        var email =
            document
            .getElementById("email")
            .value
            .trim();


        var password =
            document
            .getElementById("password")
            .value;


        var remember =
            document
            .getElementById("remember")
            .checked;



        /* CLEAR ERRORS */

        document
        .getElementById("emailError")
        .innerHTML = "";


        document
        .getElementById("passwordError")
        .innerHTML = "";


        document
        .getElementById("message")
        .innerHTML = "";



        /* EMAIL VALIDATION */

        if(email == "")
        {

            document
            .getElementById("emailError")
            .innerHTML =
                "Please enter your email";

            return;

        }



        /* PASSWORD VALIDATION */

        if(password == "")
        {

            document
            .getElementById("passwordError")
            .innerHTML =
                "Please enter your password";

            return;

        }



        /* AJAX */

        var xhr =
            new XMLHttpRequest();



        xhr.open(
            "POST",
            "index.php?url=auth/authenticate",
            true
        );



        xhr.setRequestHeader(
            "Content-Type",
            "application/x-www-form-urlencoded"
        );



        document
        .getElementById("loginButton")
        .innerHTML =
            "Logging in...";



        xhr.onload = function()
        {

            document
            .getElementById("loginButton")
            .innerHTML =
                "Login";



            if(xhr.status == 200)
            {

                try
                {

                    var response =
                        JSON.parse(
                            xhr.responseText
                        );


					if(response.success)
					{
						document
						.getElementById("message")
						.innerHTML = response.message;

						setTimeout(function()
						{
							// Use the redirect property from response
							if(response.redirect)
							{
								window.location.href = response.redirect;
							}
						}, 500);
					}

						else
						{
							document
							.getElementById("message")
							.innerHTML =
								response.message;
						}

				}
                catch(error)
                {

                    document
                    .getElementById("message")
                    .innerHTML =
                        xhr.responseText;

                }

            }
            else
            {

                document
                .getElementById("message")
                .innerHTML =
                    "Server error: " +
                    xhr.status;

            }

        };



        xhr.onerror = function()
        {

            document
            .getElementById("loginButton")
            .innerHTML =
                "Login";


            document
            .getElementById("message")
            .innerHTML =
                "Could not connect to the server.";

        };



        /* SEND DATA */

        xhr.send(

            "email=" +
            encodeURIComponent(email)

            +

            "&password=" +
            encodeURIComponent(password)

            +

            "&remember=" +
            (remember ? "1" : "0")

        );

    }

);

</script>


</body>

</html>
