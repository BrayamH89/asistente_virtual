<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
</head>
<body>
    <form method="POST" action="{{ route('register') }}">
    @csrf
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <input type="password" name="password_confirmation" required>
    <select name="role" required>
        <option value="usuario">Usuario</option>
        <option value="asesor">Asesor</option>
        <option value="admin">Administrador</option>
    </select>
    <button type="submit">Registrarse</button>
</form>

</body>
</html>