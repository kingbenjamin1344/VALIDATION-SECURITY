function calculateAge() {
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
}