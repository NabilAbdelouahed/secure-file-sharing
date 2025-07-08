<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="./login.css" />
  </head>
  <body>
    <div id="loginForm">
      <form action="./auth/login.php" method="post">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>
        <label for="password">Password</label>
        <input type="text" id="password" name="password" required>
        <button type="submit">Login</button>
      </form>
      <h3 id="loginStatus"></h3>
    </div>
    
  </body>
</html>