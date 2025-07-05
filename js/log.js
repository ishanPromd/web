// Hardcoded credentials
const credentials = new Map([
    ["admin", "pass"],
    ["ishan", "123"],
    ["chamod", "1234@#"],
    ["naveen","naveen"],
    ["poorna","1234"],
    ["tharu","tharu"],
    ["lasith","123"],
    ["duleesha,duleesha123"],
    ["pakaya","pakaya1234"],
    ["JANITH","#janitH1209"]
]);

// Handle login
document.getElementById('loginForm').addEventListener('submit', function (e) {
    e.preventDefault();

    let username = document.getElementById('username').value.trim();
    let password = document.getElementById('password').value.trim();

    console.log("Entered Username:", username); // Debugging

    if (credentials.has(username) && credentials.get(username) === password) {
        alert(`Login as ${username}!`); // Make sure this uses backticks
        window.location.href = "https://ictfromabc.great-site.net/home.html";
    } else {
        alert('Invalid username or password. Contact admin to register your username & password!');
    }
});