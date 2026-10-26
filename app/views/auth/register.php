<!DOCTYPE html>

<html>

<head>

<title>Register - EcoCycle</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial,sans-serif;
}

body{
    background:#f4f8f5;
}

/* NAVBAR */

.navbar{
    height:70px;
    background:white;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 7%;
    box-shadow:0 2px 10px rgba(0,0,0,0.08);
}

.logo{
    font-size:25px;
    font-weight:bold;
    color:#168a45;
}

.logo span{
    color:#222;
}

.navbar a{
    color:#168a45;
    text-decoration:none;
    font-weight:bold;
}

/* MAIN */

.main{
    min-height:calc(100vh - 70px);
    display:flex;
    justify-content:center;
    align-items:center;
    padding:30px;
}

/* REGISTER BOX */

.register-box{
    width:900px;
    max-width:100%;
    background:white;
    border-radius:18px;
    overflow:hidden;
    display:grid;
    grid-template-columns:45% 55%;
    box-shadow:0 8px 35px rgba(0,0,0,0.08);
}

/* LEFT */

.left{
    background:#168a45;
    color:white;
    padding:45px 35px;
}

.left h2{
    font-size:30px;
    margin-bottom:15px;
}

.left p{
    line-height:1.7;
    margin-bottom:25px;
    color:#e8fff0;
}

.benefit{
    margin:15px 0;
}

/* RIGHT */

.right{
    padding:40px 45px;
}

.right h2{
    margin-bottom:8px;
}

.subtitle{
    color:#777;
    font-size:14px;
    margin-bottom:22px;
}

/* FORM */

#registerForm{
    display:grid;
    grid-template-columns:1fr 1fr;
    column-gap:18px;
}

/* GROUP */

.group{
    margin-bottom:15px;
}

label{
    display:block;
    font-size:14px;
    font-weight:bold;
    margin-bottom:7px;
}

input,
select{
    width:100%;
    padding:11px;
    border:1px solid #ddd;
    border-radius:8px;
    font-size:14px;
}

input:focus,
select:focus{
    outline:none;
    border-color:#168a45;
}

/* ERROR */

.error{
    display:block;
    color:#d93025;
    font-size:12px;
    margin-top:5px;
}

/* MESSAGE */

.message{
    color:#d93025;
    font-size:13px;
    margin-bottom:15px;
    white-space:pre-wrap;
}

/* REGISTER ROLE */

.register-role{
    grid-column:1 / -1;
}

/* EXTRA SECTION */

.role-extra{
    grid-column:1 / -1;
    display:none;
    background:#f7faf8;
    padding:15px;
    border-radius:10px;
    margin-bottom:15px;
}

.role-extra h3{
    grid-column:1 / -1;
    font-size:15px;
    color:#168a45;
    margin-bottom:15px;
}

/* NGO */

#ngoFields{
    grid-template-columns:1fr 1fr;
    column-gap:18px;
}

#ngoFields .group:last-child{
    grid-column:1 / -1;
}

/* DELIVERY */

#deliveryFields{
    grid-template-columns:1fr;
}

/* FILE INFO */

.file-info{
    font-size:12px;
    color:#777;
    margin-top:5px;
}

/* BUTTON */

.register-btn{
    grid-column:1 / -1;
    width:100%;
    padding:13px;
    border:none;
    border-radius:8px;
    background:#168a45;
    color:white;
    font-weight:bold;
    cursor:pointer;
}

.register-btn:hover{
    background:#117239;
}

.register-btn:disabled{
    background:#999;
    cursor:not-allowed;
}

/* LOGIN */

.login{
    text-align:center;
    margin-top:18px;
    font-size:14px;
    color:#777;
}

.login a{
    color:#168a45;
    font-weight:bold;
    text-decoration:none;
}

/* MOBILE */

@media(max-width:700px){

    .register-box{
        grid-template-columns:1fr;
    }

    .left{
        display:none;
    }

    .right{
        padding:30px 22px;
    }

    #registerForm{
        grid-template-columns:1fr;
    }

    #registerForm > .group,
    .register-role,
    .role-extra,
    .register-btn{
        grid-column:1 / -1;
    }

    #ngoFields{
        grid-template-columns:1fr;
    }

    #ngoFields .group:last-child{
        grid-column:1 / -1;
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

    <a href="index.php?url=auth/login">
        Login
    </a>

</nav>


<!-- MAIN -->

<div class="main">

<div class="register-box">


<!-- LEFT SIDE -->

<div class="left">

    <h2>
        Join EcoCycle
    </h2>

    <p>
        Create your EcoCycle account and
        become part of a sustainable
        sharing community.
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


<!-- RIGHT SIDE -->

<div class="right">

<h2>
    Create Account
</h2>

<p class="subtitle">
    Fill in your details to register.
</p>

<div id="message" class="message"></div>


<form
    id="registerForm"
    enctype="multipart/form-data"
>


<!-- NAME -->

<div class="group">

    <label>
        Full Name
    </label>

    <input
        type="text"
        id="name"
        placeholder="Enter your full name"
    >

    <span
        id="nameError"
        class="error"
    ></span>

</div>


<!-- EMAIL -->

<div class="group">

    <label>
        Email Address
    </label>

    <input
        type="email"
        id="email"
        placeholder="example@email.com"
    >

    <span
        id="emailError"
        class="error"
    ></span>

</div>


<!-- PHONE -->

<div class="group">

    <label>
        Phone Number
    </label>

    <input
        type="text"
        id="phone"
        placeholder="01XXXXXXXXX"
        maxlength="11"
    >

    <span
        id="phoneError"
        class="error"
    ></span>

</div>


<!-- LOCATION -->

<div class="group">

    <label>
        Location
    </label>

    <input
        type="text"
        id="location"
        placeholder="Enter your location"
    >

    <span
        id="locationError"
        class="error"
    ></span>

</div>


<!-- ROLE -->

<div class="group register-role">

    <label>
        Register As
    </label>

    <select id="role">

        <option value="">
            Select your role
        </option>

        <option value="user">
            User
        </option>

        <option value="ngo">
            NGO
        </option>

        <option value="delivery_man">
            Delivery Man
        </option>

    </select>

    <span
        id="roleError"
        class="error"
    ></span>

</div>


<!-- NGO -->

<div
    id="ngoFields"
    class="role-extra"
>

    <h3>
        NGO Information
    </h3>


    <div class="group">

        <label>
            Organization Type
        </label>

        <select id="organizationType">

            <option value="">
                Select organization type
            </option>

            <option value="Charity">
                Charity
            </option>

            <option value="Foundation">
                Foundation
            </option>

            <option value="Social Organization">
                Social Organization
            </option>

        </select>

        <span
            id="organizationTypeError"
            class="error"
        ></span>

    </div>


    <div class="group">

        <label>
            Established Year
        </label>

        <input
            type="number"
            id="establishedYear"
            placeholder="Example: 2020"
            min="1900"
            max="2026"
        >

        <span
            id="establishedYearError"
            class="error"
        ></span>

    </div>


    <div class="group">

        <label>
            Verification Document
        </label>

        <input
            type="file"
            id="ngoDocument"
            accept=".pdf,.jpg,.jpeg,.png"
        >

        <p class="file-info">
            Upload NGO verification document.
            PDF, JPG or PNG only. Maximum 5MB.
        </p>

        <span
            id="ngoDocumentError"
            class="error"
        ></span>

    </div>

</div>


<!-- DELIVERY -->

<div
    id="deliveryFields"
    class="role-extra"
>

    <h3>
        Delivery Information
    </h3>


    <div class="group">

        <label>
            Driving License
        </label>

        <input
            type="file"
            id="drivingLicense"
            accept=".pdf,.jpg,.jpeg,.png"
        >

        <p class="file-info">
            Upload your Driving License.
            PDF, JPG or PNG only. Maximum 5MB.
        </p>

        <span
            id="drivingLicenseError"
            class="error"
        ></span>

    </div>

</div>


<!-- PASSWORD -->

<div class="group">

    <label>
        Password
    </label>

    <input
        type="password"
        id="password"
        placeholder="Enter password"
    >

    <span
        id="passwordError"
        class="error"
    ></span>

</div>


<!-- CONFIRM PASSWORD -->

<div class="group">

    <label>
        Confirm Password
    </label>

    <input
        type="password"
        id="confirmPassword"
        placeholder="Confirm password"
    >

    <span
        id="confirmError"
        class="error"
    ></span>

</div>


<!-- BUTTON -->

<button
    type="submit"
    class="register-btn"
    id="registerButton"
>

    Create Account

</button>


</form>


<div class="login">

    Already have an account?

    <a href="index.php?url=auth/login">
        Login
    </a>

</div>

</div>

</div>

</div>


<script>

/* ==================================================
   ROLE CHANGE
================================================== */

document.getElementById("role").addEventListener(
    "change",
    function()
    {

        var role = this.value;

        var ngoFields =
            document.getElementById("ngoFields");

        var deliveryFields =
            document.getElementById("deliveryFields");


        ngoFields.style.display = "none";

        deliveryFields.style.display = "none";


        if(role == "ngo")
        {
            ngoFields.style.display = "grid";
        }

        else if(role == "delivery_man")
        {
            deliveryFields.style.display = "grid";
        }

    }
);


/* ==================================================
   FORM SUBMIT
================================================== */

document.getElementById("registerForm").addEventListener(
    "submit",
    function(e)
    {

        e.preventDefault();


        /* COMMON DATA */

        var name =
            document.getElementById("name").value.trim();

        var email =
            document.getElementById("email").value.trim();

        var phone =
            document.getElementById("phone").value.trim();

        var location =
            document.getElementById("location").value.trim();

        var role =
            document.getElementById("role").value;

        var password =
            document.getElementById("password").value;

        var confirmPassword =
            document.getElementById("confirmPassword").value;


        /* NGO DATA */

        var organizationType =
            document.getElementById("organizationType").value;

        var establishedYear =
            document.getElementById("establishedYear").value;

        var ngoDocument =
            document.getElementById("ngoDocument").files[0];


        /* DELIVERY DATA */

        var drivingLicense =
            document.getElementById("drivingLicense").files[0];


        /* CLEAR ERRORS */

        document.querySelectorAll(".error").forEach(
            function(error)
            {
                error.innerHTML = "";
            }
        );

        document.getElementById("message").innerHTML = "";


        /* NAME */

        if(name == "")
        {

            document.getElementById("nameError").innerHTML =
                "Please enter your full name";

            return;

        }


        /* EMAIL */

        if(email == "")
        {

            document.getElementById("emailError").innerHTML =
                "Please enter your email address";

            return;

        }


        /* EMAIL REGEX */

        var emailPattern =
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


        if(!emailPattern.test(email))
        {

            document.getElementById("emailError").innerHTML =
                "Please enter a valid email address";

            return;

        }


        /* PHONE */

        if(phone == "")
        {

            document.getElementById("phoneError").innerHTML =
                "Please enter your phone number";

            return;

        }


        var phonePattern =
            /^01[3-9]\d{8}$/;


        if(!phonePattern.test(phone))
        {

            document.getElementById("phoneError").innerHTML =
                "Enter a valid Bangladeshi phone number";

            return;

        }


        /* LOCATION */

        if(location == "")
        {

            document.getElementById("locationError").innerHTML =
                "Please enter your location";

            return;

        }


        /* ROLE */

        if(role == "")
        {

            document.getElementById("roleError").innerHTML =
                "Please select your role";

            return;

        }


        /* NGO VALIDATION */

        if(role == "ngo")
        {

            if(organizationType == "")
            {

                document.getElementById(
                    "organizationTypeError"
                ).innerHTML =
                    "Please select organization type";

                return;

            }


            if(establishedYear == "")
            {

                document.getElementById(
                    "establishedYearError"
                ).innerHTML =
                    "Please enter established year";

                return;

            }


            if(!ngoDocument)
            {

                document.getElementById(
                    "ngoDocumentError"
                ).innerHTML =
                    "Please upload verification document";

                return;

            }

        }


        /* DELIVERY VALIDATION */

        if(role == "delivery_man")
        {

            if(!drivingLicense)
            {

                document.getElementById(
                    "drivingLicenseError"
                ).innerHTML =
                    "Please upload your Driving License";

                return;

            }

        }


        /* PASSWORD */

        if(password == "")
        {

            document.getElementById("passwordError").innerHTML =
                "Please enter your password";

            return;

        }


        if(password.length < 6)
        {

            document.getElementById("passwordError").innerHTML =
                "Password must be at least 6 characters";

            return;

        }


        /* CONFIRM PASSWORD */

        if(confirmPassword == "")
        {

            document.getElementById("confirmError").innerHTML =
                "Please confirm your password";

            return;

        }


        if(password != confirmPassword)
        {

            document.getElementById("confirmError").innerHTML =
                "Passwords do not match";

            return;

        }


        /* ==================================================
           FORM DATA
        ================================================== */

        var formData = new FormData();


        formData.append("name", name);

        formData.append("email", email);

        formData.append("phone", phone);

        formData.append("location", location);

        formData.append("role", role);

        formData.append("password", password);


        /* NGO */

        if(role == "ngo")
        {

            formData.append(
                "organization_type",
                organizationType
            );

            formData.append(
                "established_year",
                establishedYear
            );

            formData.append(
                "verification_document",
                ngoDocument
            );

        }


        /* DELIVERY */

        if(role == "delivery_man")
        {

            formData.append(
                "verification_document",
                drivingLicense
            );

        }


        /* ==================================================
           AJAX
        ================================================== */

        var xhr = new XMLHttpRequest();


        xhr.open(
            "POST",
            "index.php?url=auth/store",
            true
        );


        document.getElementById("registerButton").innerHTML =
            "Creating Account...";

        document.getElementById("registerButton").disabled =
            true;


        xhr.onload = function()
        {

            document.getElementById("registerButton").innerHTML =
                "Create Account";

            document.getElementById("registerButton").disabled =
                false;


            if(xhr.status == 200)
            {

                try
                {

                    var responseText =
                        xhr.responseText.trim();

                    var response =
                        JSON.parse(responseText);


                    if(response.success)
                    {

                        window.location.href =
                            "index.php?url=auth/login&registered=1";

                    }

                    else
                    {

                        document.getElementById("message").innerHTML =
                            response.message ||
                            "Registration failed.";

                    }

                }

                catch(error)
                {

                    document.getElementById("message").innerHTML =
                        "Server returned invalid response:<br>" +
                        xhr.responseText;

                }

            }

            else
            {

                document.getElementById("message").innerHTML =
                    "Server error: " + xhr.status;

            }

        };


        xhr.onerror = function()
        {

            document.getElementById("registerButton").innerHTML =
                "Create Account";

            document.getElementById("registerButton").disabled =
                false;


            document.getElementById("message").innerHTML =
                "Could not connect to the server.";

        };


        /* SEND */

        xhr.send(formData);

    }

);

</script>

</body>

</html>
