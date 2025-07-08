<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login</title>
  </head>
  <body>
    <div id="authForm">
      <form method="pos" action="./auth/login.php" method="post">
        <label for="username">Username</label><br>
        <input type="text" id="username" name="username" required><br>
        <label for="password">Password</label><br>
        <input type="text" id="password" name="password" required><br>
        <input type="submit" value="Submit">
      </form>
    </div>
  </body>
</html>