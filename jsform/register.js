function validateForm(){

    var id_no=document.forms["myform"]["id_no"].value;
    var firstname=document.forms["myform"]["firstname"].value;
    var middlename=document.forms["myform"]["middlename"].value;
    var lastname=document.forms["myform"]["lastname"].value;
    var suffix=document.forms["myform"]["suffix"].value;
    var sex=document.forms["myform"]["sex"].value;
    var purok=document.forms["myform"]["purok"].value;
    var barangay=document.forms["myform"]["barangay"].value;
    var municipality=document.forms["myform"]["municipality"].value;
    var province=document.forms["myform"]["province"].value;
    var country=document.forms["myform"]["country"].value;
    var zipcode=document.forms["myform"]["zipcode"].value;
    var email=document.forms["myform"]["email"].value;
    var username=document.forms["myform"]["username"].value;
    var password=document.forms["myform"]["password"].value;
    var reenterpassword=document.forms["myform"]["reenterpassword"].value;


  
    

   // var existingPasswords = ["password123", "admin123", "welcome2024", "qwerty","kingbenjamin","johnyuri","bulletpunch","johnrey@123"];
    
  //  const birthdateInput = document.forms["myform"]["birthdate"].value;
    //const age = document.forms["myform"]["age"].value;


    var errorMessage = document.getElementById("error-message");
    var errorMessagefirst = document.getElementById("error-messagefirst");
    var errorMessagelast = document.getElementById("error-messagelast");
    var errorMessagemid = document.getElementById("error-messagemid");
    var errorMessagepurok = document.getElementById("error-messagepurok");
    var errorMessagebarangay = document.getElementById("error-messagebarangay");
    var errorMessagemunicipality = document.getElementById("error-messagemunicipality");
    var errorMessageprovince = document.getElementById("error-messageprovince");
    var errorMessagecountry = document.getElementById("error-messagecountry");
    var errorMessageuser = document.getElementById("error-messageuser");
    var errorMessagezipcode = document.getElementById("messagezipcode");
    var errorMessageemail = document.getElementById("messageemail");

    var errorMessagebirth = document.getElementById("messagebirth");
    var errorMessageage = document.getElementById("messageage");



    var message = document.getElementById("message");
    var message9 = document.getElementById("message9");
    var agreement = document.forms["myform"]["agreement"].checked;





     // Clear previous error message
  errorMessage.innerHTML = "";
  errorMessagefirst.innerHTML = "";
  errorMessagelast.innerHTML = "";
  errorMessagemid.innerHTML = "";
  errorMessagepurok.innerHTML = "";
  errorMessagebarangay.innerHTML = "";
  errorMessagemunicipality.innerHTML = "";
  errorMessageprovince.innerHTML = "";
  errorMessagecountry.innerHTML = "";
  errorMessageuser.innerHTML = "";
  errorMessagezipcode.innerHTML = "";
  errorMessageemail.innerHTML = "";
       
  

/////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
///////////////////////////////////////////////////ID number***********************************************************************************************************
var regexid = /^\d{4}-\d{4}$/;
if (!regexid.test(id_no)) {
    alert("Invalid format. Please enter ID in the format xxxx-xxxx.");
    return false;
}


if(id_no.trim()===""){
     alert("Error: ID Number Cannot Be Empty");
    return false;
}
// Check if the input contains any spaces
if (/\s/.test(id_no)) {
        alert("Error: ID number should not contain spaces.");
        return false;
}

    // Check if the input contains any letters
if (/[a-zA-Z]/.test(id_no)) {
        alert("Error: ID number should not contain letters.");
        return false;
}
var regex = /^[0-9-]+$/;

// If the input doesn't match the regex, display an error
if (!regex.test(id_no)) {
    alert("Error: Only hyphens (-) are allowed.");
    return false;  // Prevent form submission
}
if (id_no.length < 4) {
    errorMessage.innerHTML = "ID must exceed 3 numbers ";
    return false; // Prevent form submission
  }
  if ( id_no.length > 15) {
    errorMessage.innerHTML = "must not exceed 15 numbers ";
    return false; // Prevent form submission
  }
  if (id_no.length > 0) {
    // Create an AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "check_id.php?id_no=" + encodeURIComponent(id_no), true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            if (xhr.responseText === "exists") {
                alert("ID number already exists. Please enter another.");
                return false; // Prevent form submission
            }
        }
    };
    xhr.send();
}

/////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
//////////////////////////////////////////////////First Name***********************************************************************************************************
// Check if length of id_no is between 2 and 25

var words = firstname.split(" ");

    // Loop through each word and check if it has exactly two uppercase letters
    for (var i = 0; i < words.length; i++) {
        var word = words[i];

        // Count the number of uppercase letters in the word
        var uppercaseCount = word.replace(/[^A-Z]/g, "").length;

        if (uppercaseCount == 2) {
            alert("Error:  firstname must not contain exactly two uppercase letters per word.");
            return false; // Prevent form submission
        }
    }

if (/^\s/.test(firstname)) {
    alert("Error: First Name must not start with space");//check if firsname start with space
    return false;
}
if(firstname.trim()===""){
        alert("Error: First Name Must Not Be Empty");//check if firstname is empty
        return false;
}
if (firstname.length < 2 ) {
    errorMessagefirst.innerHTML = "must exceed one letter";
    return false; // Prevent form submission
  }

  if ( firstname.length > 25) {
    errorMessagefirst.innerHTML = "must not exceed 25 letters ";
    return false; // Prevent form submission
  }



if (firstname === firstname.toUpperCase() && firstname !== "") {
    alert("The firstname must not all capital letters!");
    return false; // prevent form submission
}
if (/\d/.test(firstname)) {
       alert("Error: First Name must not contain numbers");//check if firstname has number
       return false;
}

if (/\s{2,}/.test(firstname)) {
        alert("Error: First Name must not contain double spaces");//check if firstname has double spaces
        return false;
}
if (/[^a-zA-Z\s]/.test(firstname)) {
        alert("Error: First Name must not contain symbols");//check if firstname has symbols
        return false;
}
    //check if firstname contain 3 consecutive same letters
if (/([a-zA-Z])\1{2}/.test(firstname)) {
        alert("Error: First name should not contain three consecutive same letters.");
        return false;
}
    //check if firstname contain 2 uppercase consecutives same letter 
if (/([A-Z])\1/.test(firstname)) {
        alert("Error: First name should not contain two consecutive same uppercase letters.");
        return false;
}
    
if (!/^[A-Z]/.test(firstname)) {
        alert("Error: First Name must start with Capital");//check if firstname if its capital
        return false;
    }
if (/\s+[a-z]/.test(firstname)) {
        alert("Error: First Name must start with Capital after Space");//check if firstname if its capital after space
        return false;
}


  
/////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
//////////////////////////////////////////////////Middle Name***********************************************************************************************************
 
if (/^\s/.test(middlename)) {
    alert("Error: Middle Initial must not start with space");//check if Middle Initial start with space
    return false;
}
if (/\d/.test(middlename)) {
        alert("Middle Initial should not contain numbers.");// check if it has numbers
        return false;
}
if (middlename && !/^[A-Z]/.test(middlename)) {
    alert("Error: Middle Initial must start with a capital letter.");
    return false;
}
if (/[^a-zA-Z\s]/.test(middlename)) {
    alert("Error: Middle Initial must not contain symbols");//check if Middle Initial has symbols
    return false;
}
if (/\s{2,}/.test(middlename)) {
    alert("Error: middlename  must not contain double spaces");//check if middlename has double spaces
    return false;
}
if ( middlename.length > 1) {
    errorMessagemid.innerHTML = "must not exceed 1 letter ";
    return false; // Prevent form submission
  }

 
/////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
//////////////////////////////////////////////////Last Name***********************************************************************************************************


var words = lastname.split(" ");

    // Loop through each word and check if it has exactly two uppercase letters
    for (var i = 0; i < words.length; i++) {
        var word = words[i];

        // Count the number of uppercase letters in the word
        var lastuppercaseCount = word.replace(/[^A-Z]/g, "").length;

        if (lastuppercaseCount == 2) {
            alert("Error:  Last Name must not contain exactly two uppercase letters per word.");
            return false; // Prevent form submission
        }
    }
if (/^\s/.test(lastname)) {
        alert("Error: Last Name must not start with space");//check if lastname start with space
        return false;
 }
if(lastname.trim()===""){
        alert("Error: Last Name Must Not Be Empty");//check if lastname is empty
        return false;
}

if (lastname.length < 2 ) {
    errorMessagelast.innerHTML = " must exceed one letter";
    return false; // Prevent form submission
  }

  if ( lastname.length > 25) {
    errorMessagelast.innerHTML = "must not exceed 25 letters ";
    return false; // Prevent form submission
  }
  
if (lastname === lastname.toUpperCase() && lastname !== "") {
    alert("The lastname must not all capital letters!");
    return false; // prevent form submission
}
if (/\d/.test(lastname)) {
       alert("Error: Last Name must not contain numbers");//check if lastname has number
       return false;
}

if (/\s{2,}/.test(lastname)) {
        alert("Error: Last Name must not contain double spaces");//check if lastname has double spaces
        return false;
}
if (/[^a-zA-Z\s]/.test(lastname)) {
        alert("Error: Last Name must not contain symbols");//check if lastname has symbols
        return false;
}
//check if lastname contain 2 uppercase consecutive letter
if (/([A-Z]{2})/.test(lastname)) {
        alert("Error: Last name should not contain two uppercase consecutive letters in the same case.");
        return false;
}
if (!/^[A-Z]/.test(lastname)) {
        alert("Error: Last Name must start with Capital");//check if lastname if its capital
        return false;
}
if (/\s+[a-z]/.test(lastname)) {
    alert("Error: Last Name must start with Capital after Space");//check if lastname if its capital after space
    return false;
}
    //check if firstname contain 3 consecutive same letters
    if (/([a-zA-Z])\1{2}/.test(lastname)) {
        alert("Error: Lastname name should not contain three consecutive same letters.");
        return false;
}


/////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************


///////////////////////////////////////////////////Enail***********************************************************************************************************


const birthdate = document.getElementById('birthdate').value;
const ageInput = document.getElementById('age');


if (birthdate === "") {
    alert("Birthdate must not be empty");
    ageInput.value = ""; // Clear age input if no birthdate is provided
    return;
}

const birthDateObj = new Date(birthdate);
const currentDate = new Date();

let age = currentDate.getFullYear() - birthDateObj.getFullYear();
const month = currentDate.getMonth();
const day = currentDate.getDate();

// Adjust age if birthdate hasn't occurred yet this year
if (month < birthDateObj.getMonth() || (month === birthDateObj.getMonth() && day < birthDateObj.getDate())) {
    age--;
}

ageInput.value = age;

if (age < 18) {
    alert("You must be 18");
    ageInput.value = ""; // Clear age input if under 18
}

// Check if the input contains any spaces

/////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
///////////////////////////////////////////////////Enail***********************************************************************************************************
if(email.trim()===""){
    alert("Error: email  Cannot Be Empty");
   return false;
}

if ( email.length < 15) {
    errorMessageemail.innerHTML = "must exceed 15 character ";
    return false; // Prevent form submission
 }
  

 // if ( email.length > 30) {
//    errorMessageemail.innerHTML = "must not exceed 30 character ";
//    return false; // Prevent form submission
//  }


/////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
//////////////////////////////////////////////////Sex***********************************************************************************************************
if (sex === "") {
    alert("Sex cannot be empty.");
    return false;
}
/////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
//////////////////////////////////////////////////Purok***********************************************************************************************************



var words = purok.split(" ");

    // Loop through each word and check if it has exactly two uppercase letters
    for (var i = 0; i < words.length; i++) {
        var word = words[i];

        // Count the number of uppercase letters in the word
        var purokuppercaseCount = word.replace(/[^A-Z]/g, "").length;

        if (purokuppercaseCount == 2) {
            alert("Error:  Purok must not contain exactly two uppercase letters per word.");
            return false; // Prevent form submission
        }
    }

if (/^\s/.test(purok)) {
    alert("Error: purok  must not start with space");//check if purok start with space
    return false;
 }
if(purok.trim()===""){
    alert("Error: purok Must Not Be Empty");//check if purok is empty
    return false;
}

if (purok.length < 7 ) {
    errorMessagepurok.innerHTML = "must exceed 6 letters";
    return false; // Prevent form submission
  }

  if ( purok.length > 25) {
    errorMessagepurok.innerHTML = "must not exceed 25 letters ";
    return false; // Prevent form submission
  }
if (purok === purok.toUpperCase() && purok !== "") {
    alert("The purok must not all capital letters!");
    return false; // prevent form submission
}

if (/\s{2,}/.test(purok)) {
    alert("Error: purok  must not contain double spaces");//check if purok has double spaces
    return false;
}
//check if purok contain 2 uppercase consecutive letter
if (/([A-Z]{2})/.test(purok)) {
    alert("Error: purok  should not contain two uppercase consecutive letters in the same case.");
    return false;
}
if (!/^[A-Z]/.test(purok)) {
    alert("Error: purok  must start with Capital");//check if purok if its capital
    return false;
}

    //check if firstname contain 3 consecutive same letters
    if (/([a-zA-Z])\1{2}/.test(purok)) {
        alert("Error: Purok should not contain three consecutive same letters.");
        return false;
}
if (/\s+[a-z]/.test(purok)) {
    alert("Error: purok must start with Capital after Space");//check if purok if its capital after space
    return false;
}
var regex = /^[a-zA-Z0-9\s\-]+$/;

if (!regex.test(purok)) {
    alert("Purok should only allow hyphens.");
    return false; // Prevent form submission
}


  

/////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
//////////////////////////////////////////////////Barangay***********************************************************************************************************




var words = barangay.split(" ");

    // Loop through each word and check if it has exactly two uppercase letters
    for (var i = 0; i < words.length; i++) {
        var word = words[i];

        // Count the number of uppercase letters in the word
        var barangayuppercaseCount = word.replace(/[^A-Z]/g, "").length;

        if (barangayuppercaseCount == 2) {
            alert("Error:  Barangay must not contain exactly two uppercase letters per word.");
            return false; // Prevent form submission
        }
    }

if (/^\s/.test(barangay)) {
    alert("Error: barangay  must not start with space");//check if barangay start with space
    return false;
 }
if(barangay.trim()===""){
    alert("Error: barangay Must Not Be Empty");//check if barangay is empty
    return false;
}
if (barangay.length < 4 ) {
    errorMessagebarangay.innerHTML = " must exceed 3 letter";
    return false; // Prevent form submission
  }

  if ( barangay.length > 25) {
    errorMessagebarangay.innerHTML = "must not exceed 25 letters ";
    return false; // Prevent form submission
  }
  
if (barangay === barangay.toUpperCase() && barangay !== "") {
    alert("The barangay must not all capital letters!");
    return false; // prevent form submission
}
if (/\s{2,}/.test(barangay)) {
    alert("Error: barangay  must not contain double spaces");//check if barangay has double spaces
    return false;
}
//check if purok contain 2 uppercase consecutive letter
if (/([A-Z]{2})/.test(barangay)) {
    alert("Error: barangay  should not contain two uppercase consecutive letters in the same case.");
    return false;
}
if (!/^[A-Z]/.test(barangay)) {
    alert("Error: barangay  must start with Capital");//check if barangay if its capital
    return false;
}
    //check if firstname contain 3 consecutive same letters
    if (/([a-zA-Z])\1{2}/.test(barangay)) {
        alert("Error: Barangay should not contain three consecutive same letters.");
        return false;
}
if (/\s+[a-z]/.test(barangay)) {
    alert("Error: barangay must start with Capital after Space");//check if barangay if its capital after space
    return false;
}
var regexter = /^[a-zA-Z0-9\s\-]+$/;

if (!regexter.test(barangay)) {
    alert("Purok should only contain alphanumeric characters, spaces, and hyphens.");
    return false; // Prevent form submission
}



/////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
//////////////////////////////////////////////////Municipality***********************************************************************************************************

  
var words = municipality.split(" ");

    // Loop through each word and check if it has exactly two uppercase letters
    for (var i = 0; i < words.length; i++) {
        var word = words[i];

        // Count the number of uppercase letters in the word
        var municipalityuppercaseCount = word.replace(/[^A-Z]/g, "").length;

        if (municipalityuppercaseCount == 2) {
            alert("Error:  Municipality must not contain exactly two uppercase letters per word.");
            return false; // Prevent form submission
        }
    }

if (/^\s/.test(municipality)) {
    alert("Error: municipality/city  must not start with space");//check if municipality start with space
    return false;
 }
if(municipality.trim()===""){
    alert("Error: municipality/city Must Not Be Empty");//check if municipality is empty
    return false;
}
if (municipality.length < 4 ) {
    errorMessagemunicipality.innerHTML = " must exceed 3 letter";
    return false; // Prevent form submission
  }

  if ( municipality.length > 25) {
    errorMessagemunicipality.innerHTML = "must not exceed 25 letters ";
    return false; // Prevent form submission
  }
if (municipality === municipality.toUpperCase() && municipality !== "") {
    alert("The municipality must not all capital letters!");
    return false; // prevent form submission
}
if (/\s{2,}/.test(municipality)) {
    alert("Error: municipality/city  must not contain double spaces");//check if municipality has double spaces
    return false;
}
//check if purok contain 2 uppercase consecutive letter
if (/([A-Z]{2})/.test(municipality)) {
    alert("Error: municipality/city  should not contain two uppercase consecutive letters in the same case.");
    return false;
}
if (!/^[A-Z]/.test(municipality)) {
    alert("Error: municipality/city  must start with Capital");//check if municipality if its capital
    return false;
}
if (/\s+[a-z]/.test(municipality)) {
    alert("Error: municipality must start with Capital after Space");//check if municipality if its capital after space
    return false;
}

    //check if firstname contain 3 consecutive same letters
    if (/([a-zA-Z])\1{2}/.test(municipality)) {
        alert("Error: Municipality or City should not contain three consecutive same letters.");
        return false;
}

/////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
//////////////////////////////////////////////////Province***********************************************************************************************************

var words = province.split(" ");

    // Loop through each word and check if it has exactly two uppercase letters
    for (var i = 0; i < words.length; i++) {
        var word = words[i];

        // Count the number of uppercase letters in the word
        var provinceuppercaseCount = word.replace(/[^A-Z]/g, "").length;

        if (provinceuppercaseCount == 2) {
            alert("Error:  Province must not contain exactly two uppercase letters per word.");
            return false; // Prevent form submission
        }
    }

if (/^\s/.test(province)) {
    alert("Error: province  must not start with space");//check if province start with space
    return false;
 }
if(province.trim()===""){
    alert("Error: province Must Not Be Empty");//check if province is empty
    return false;
}
if (province.length < 4 ) {
    errorMessageprovince.innerHTML = " must exceed 3 letter";
    return false; // Prevent form submission
  }

  if ( province.length > 25) {
    errorMessageprovince.innerHTML = "must not exceed 25 letters ";
    return false; // Prevent form submission
  }
if (province === province.toUpperCase() && province !== "") {
    alert("The province must not all capital letters!");
    return false; // prevent form submission
}
if (/\s{2,}/.test(province)) {
    alert("Error: province  must not contain double spaces");//check if province has double spaces
    return false;
}
//check if province contain 2 uppercase consecutive letter
if (/([A-Z]{2})/.test(province)) {
    alert("Error: province  should not contain two uppercase consecutive letters in the same case.");
    return false;
}
if (!/^[A-Z]/.test(province)) {
    alert("Error: province  must start with Capital");//check if province if its capital
    return false;
}
if (/\s+[a-z]/.test(province)) {
    alert("Error: province must start with Capital after Space");//check if province if its capital after space
    return false;
}


    //check if firstname contain 3 consecutive same letters
    if (/([a-zA-Z])\1{2}/.test(province)) {
        alert("Error: Province should not contain three consecutive same letters.");
        return false;
}

/////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
//////////////////////////////////////////////////Country***********************************************************************************************************

var words = country.split(" ");

    // Loop through each word and check if it has exactly two uppercase letters
    for (var i = 0; i < words.length; i++) {
        var word = words[i];

        // Count the number of uppercase letters in the word
        var countryuppercaseCount = word.replace(/[^A-Z]/g, "").length;

        if (countryuppercaseCount == 2) {
            alert("Error:  Country must not contain exactly two uppercase letters per word.");
            return false; // Prevent form submission
        }
    }

if (/^\s/.test(country)) {
    alert("Error: country  must not start with space");//check if country start with space
    return false;
 }
if(country.trim()===""){
    alert("Error: country Must Not Be Empty");//check if country is empty
    return false;
}
if (country === country.toUpperCase() && country !== "") {
    alert("The country must not all capital letters!");
    return false; // prevent form submission
}

if (/\s{2,}/.test(country)) {
    alert("Error: country  must not contain double spaces");//check if country has double spaces
    return false;
}
//check if country contain 2 uppercase consecutive letter
if (/([A-Z]{2})/.test(country)) {
    alert("Error: country  should not contain two uppercase consecutive letters in the same case.");
    return false;
}
if (!/^[A-Z]/.test(country)) {
    alert("Error: country  must start with Capital");//check if country if its capital
    return false;
}
if (/\s+[a-z]/.test(country)) {
    alert("Error: country must start with Capital after Space");//check if country if its capital after space
    return false;
}
    //check if firstname contain 3 consecutive same letters
    if (/([a-zA-Z])\1{2}/.test(country)) {
        alert("Error: Country should not contain three consecutive same letters.");
        return false;
}
///////////////////////////////////////////////////////////////////////
if (/\s/.test(zipcode)) {
    alert("Error: zipcode should not contain spaces.");
    return false;
}

// Check if the input contains any letters
if (/[a-zA-Z]/.test(zipcode)) {
    alert("Error: zipcode should not contain letters.");
    return false;
}
if (/[^a-zA-Z0-9\s]/.test(zipcode)) {
    alert("Error: zipcode should not contain symbols.");
    return false;
}
if(zipcode.trim()===""){
 alert("Error: zipcode  Cannot Be Empty");
return false;
}
if ( zipcode.length < 4) {
 errorMessagezipcode.innerHTML = "must  exceed 3 numbers ";
 return false; // Prevent form submission
}
if ( zipcode.length > 4) {
 errorMessagezipcode.innerHTML = "must not exceed 4 numbers ";
 return false; // Prevent form submission
}
/////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
///////////////////////////////////////////////////username***********************************************************************************************************
if (username.length < 8 || username.length > 30) {
    errorMessageuser.innerHTML = "must be between 8 and 30 ";
    return false;  // Prevent form submission
  }
if(username.trim()===""){
    alert("Error: Username cannot be empty.");
    return false;
}

// Check if username contains spaces
if (/\s/.test(username)) {
    alert("Error: Username should not contain spaces.");
    return false;
}
if (username.length > 0) {
    // Create an AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "check_username.php?username=" + encodeURIComponent(username), true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            if (xhr.responseText === "exists") {
                alert( "Username already exists. Please choose another.");
                return false;
                
            }
        }
    };
    xhr.send();

}





  /////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
///////////////////////////////////////////////////password***********************************************************************************************************
 // Check password strength
 
 
 if (password.length < 8) {
    message.textContent = "Your password is weak";
    message.className = "weak";
    return false;


} else if (password.length >= 8) {
    // Check if password contains both numbers and symbols
    var hasNumber = /\d/;
    var hasSymbol = /[!@#$%^&*(),.?":{}|<>]/;

    if (hasNumber.test(password) && hasSymbol.test(password)) {
        message.textContent = "Your password is strong";
        message.className = "strong";

    } else {
        message.textContent = "Your password is moderate";
        message.className = "moderate";

    }
}


 /////////////////////////////////////////////////Simple Restrictions*****************************************************************************************************
///////////////////////////////////////////////////reenterpassword***********************************************************************************************************
if (reenterpassword === "") {

    message9.textContent = "Password cannot be empty!";
    message9.style.color = "red";


    return false;
}

if (password === reenterpassword) {
    message9.textContent = "Password matched.";
    message9.style.backgroundColor = "none";
    message9.style.color = "green";
} else {
    message9.textContent = "Password not matched";
    message9.style.backgroundColor = "none";
    message9.style.color = "red";
    return false;
}


  
if (!agreement) {
    alert("You must agree to the terms and condition.");
    return false;
}




 }
 