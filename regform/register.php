<?php
session_start();
include("../regform/config.php");

if($_SERVER["REQUEST_METHOD"]=="POST"){
    //Retrieve Registration Form Data
    $id_no=$_POST["id_no"];
    $firstname=$_POST["firstname"];
    $middlename=$_POST["middlename"];
    $lastname=$_POST["lastname"];
    $suffix=$_POST["suffix"];
    $sex=$_POST["sex"];
    $purok=$_POST["purok"];
    $barangay=$_POST["barangay"];
    $municipality=$_POST["municipality"];
    $province=$_POST["province"];
    $country=$_POST["country"];
    $zipcode=$_POST["zipcode"];

    $birthdate=$_POST["birthdate"];
    $age=$_POST["age"];

    $email=$_POST["email"];
    $username=$_POST["username"];
    $password=$_POST["password"];
    $reenterpassword=$_POST["reenterpassword"];

    
   
    //check if username is already used
   // $check_username_sql="SELECT * FROM users WHERE username ='$username' ";
//    $check_username_result=$conn->query($check_username_sql);

    

 //   $check_id_sql="SELECT * FROM users WHERE id_no ='$id_no' ";
//    $check_id_result=$conn->query($check_id_sql);
 


 ///  if($check_id_result->num_rows > 0){
   //     echo '<script>alert("Id Number is already used. Please Choose a different Id number."); window.location.href="../regform/register.php";</script>';
  //  }

   // if($check_username_result->num_rows > 0){
     //  echo '<script>alert("Username is already used. Please Choose a different username."); window.location.href="../regform/register.php";</script>';
 //   }


    //else{
    //Hashed The Password
        $hashed_password=password_hash($password, PASSWORD_DEFAULT);

    //Build and Execute the SQl query
        $sql="INSERT INTO users (id_no,firstname,middlename,lastname,suffix,sex,purok,barangay,municipality,province,country,zipcode,email,username,birthdate,age,password)
        VALUES ('$id_no','$firstname','$middlename','$lastname','$suffix','$sex','$purok','$barangay','$municipality','$province','$country','$zipcode','$email','$username','$birthdate','$age','$hashed_password')";

    if(mysqli_query($conn, $sql)){
        echo "<script>alert('You have Successfully created and Account!'); window.location.href='../logform/login.php'; </script>";
    }else{
        echo "Error: ".$sql."<br>".mysqli_error($conn);
    }
    }
    
//}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link rel="stylesheet" href="../css/main.register.css">

</head>
<body>
<a href="javascript:history.back()" style="position: absolute; top: 10px; left: 10px; display: flex; align-items: center; justify-content: center; width: 50px; height: 50px; background-color: #f0f0f0; color: #333; border-radius: 50%; border: 1px solid #ccc; font-weight: bold; font-size: 24px; text-decoration: none;">&#8249;</a>

<div class="container">
<center><div class="title">Registration Form<br><br></div></center>
<div class="content">
        <form name="myform" method="post" autocomplete="off" onsubmit="return validateForm()">
            
        <div class="user-details">
            <div class="input-box">
          <span class="details">ID Number</span>
                    <input type="text" class="input" name="id_no" placeholder="xxxx-xxxx">
                   
                    <span id="error-message" style="color:red;"></span>
                </div>


          <div class="input-box">
          <span class="details">First Name</span>
                    <input type="text" class="input" name="firstname" >
                    <span id="error-messagefirst" style="color:red;"></span>
                </div>

          <div class="input-box">
            <span class="details">Middle Initial</span>
                    <input type="text" class="input" name="middlename">
                    <span id="error-messagemid" style="color:red;"></span>

                    </div>

                    <div class="input-box">
            <span class="details">Last Name</label>
                    <input type="text" class="input" name="lastname">
                    <span id="error-messagelast" style="color:red;"></span>
                </div>

                <div class="input-box">
    <label>Suffix</label>
    <div class="custom_select">
        <input list="suffix-options" name="suffix" class="input-box1" placeholder="Select or type">
        <datalist id="suffix-options">
            <option value="Jr.">
            <option value="Sr.">
            <option value="I">
            <option value="II">
            <option value="III">
            <option value="IV">
        </datalist>
    </div>
</div>

               


                <div class="input-box">
        <span class="details">Birth Date</span>
        <input type="date" class="input" name="birthdate" id="birthdate" onchange="return validateForm()">
    </div>

    <div class="input-box">
        <span class="details">Age</span>
        <input type="text" class="input" name="age" id="age" readonly>
    </div>
               

                <div class="input-box">
            <span class="details">Email</span>
                    <input type="email" class="input" name="email">
                    <span id="messageemail" style="color:red;"></span>
                </div>


                <div class="input-box">
                    <label>Sex</label>
                    <div class="custom_select">
                        <select name="sex" class="input-box1">
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                </div>
                </div>
                <hr>
                
                
                
                

                <hr>

                <div class="user-details">
                <div class="input-box">
            <span class="details">Purok</span>
                    <input type="text" class="input" name="purok">
                    <span id="error-messagepurok" style="color:red;"></span>
                </div>

              <div class="input-box">
            <span class="details">Barangay</span>
                    <input type="text" class="input" name="barangay">
                    <span id="error-messagebarangay" style="color:red;"></span>
                </div>

               <div class="input-box">
            <span class="details">Municipality/City</span>
                    <input type="text" class="input" name="municipality">
                    <span id="error-messagemunicipality" style="color:red;"></span>
                </div>
                <div class="input-box">
            <span class="details">Province</span>
                    <input type="text" class="input" name="province">
                    <span id="error-messageprovince" style="color:red;"></span>
                </div>

             
                <div class="input-box">
            <span class="details">Country</span>
                    <input type="text" class="input" name="country">
                    <span id="error-messagecountry" style="color:red;"></span>
                </div>
                <div class="input-box">
            <span class="details">Zip Code</span>
                    <input type="text" class="input" name="zipcode">
                    <span id="messagezipcode" style="color:red;"></span>
                </div>


<!---->
                <div class="input-box">
            <span class="details">Username</span>
                    <input type="text" class="input" name="username" oninput="return validateForm()" >
                    <span id="error-messageuser" style="color:red;"></span>
                </div>
                <div class="input-box">
            <span class="details">Password</span>
                    <input type="password" class="input" name="password" oninput="return validateForm()">
                    <span id="message"></span>
                    <span></span>
                </div>

                <div class="input-box">
            <span class="details">Re-Enter Password</span>
                    <input type="password" class="input" name="reenterpassword" oninput="return validateForm()">
                    <span id="message9"></span>
                    <span></span>
                </div>

                <div class="inputfield terms">
                    <label class="check">
                        <input type="checkbox" name="agreement">
                        <span class="checkmark"></span>
                    </label>
                    <p>Agreed to terms and conditions</p>
                </div>
                <div class="button">
                    <input type="submit" value="Register" class="btn">
                </div>
            </div>
</div>
            <p style="text-align: center;">Already registered? <a href="../logform/login.php">Log in here</a></p>
        </form>
    </div>
    <!--  <script src="../jsform/obf_register.js"></script>
 <script src="../jsform/register.js"></script>-->
 <script src="../regform/script.php?dir=jsform&file=register.js" defer></script>
 <script src="../regform/script.php?dir=jsform&file=calculate.js" defer></script>
 <script src="../regform/script.php?dir=jsform&file=check.password.js" defer></script>
 

</body>
</html>






            