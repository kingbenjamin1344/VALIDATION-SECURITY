function validateForm(){
    var username=document.forms["myform"]["username"].value;
    var password=document.forms["myform"]["password"].value;

    var errorMessageusername = document.getElementById("usernameerrormsg");

    errorMessageusername.innerHTML = "";

    
    // Check if username is empty
    if (username === "") {
      alert("Username must not be empty");
      return false; // Prevent form submission
  }

  // Check if password is empty
  if (password === "") {
      alert("Password must not be empty");
      return false; // Prevent form submission
  }






    if (username.length < 8) {
        errorMessageusername.innerHTML = "Username required to be 8 characters ";
        return false; // Prevent form submission
      }
      if ( username.length > 30) {
        errorMessageusername.innerHTML = "Username must not exceed 30 characters ";
        return false; // Prevent form submission
      }
      
    

}
