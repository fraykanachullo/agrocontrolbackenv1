<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - AgroControl</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f4f6f4;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            max-width: 480px;
            width: 100%;
            padding: 40px 32px;
            text-align: center;
        }
        .icon {
            width: 64px;
            height: 64px;
            background: #dff1e0;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
        }
        h1 {
            color: #0d631b;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        p {
            color: #55665a;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .token {
            background: #f0f7f0;
            border: 1px dashed #9cc9a1;
            border-radius: 12px;
            padding: 16px;
            font-family: ui-monospace, "Cascadia Code", Consolas, monospace;
            font-size: 18px;
            font-weight: 600;
            color: #0d631b;
            letter-spacing: 1px;
            word-break: break-all;
            user-select: all;
        }
        .steps {
            text-align: left;
            font-size: 13px;
            color: #55665a;
            margin-top: 20px;
            line-height: 2;
        }
        .brand {
            margin-top: 24px;
            font-size: 12px;
            color: #8aa08f;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🌱</div>
        <h1>Restablecer tu contraseña</h1>
        <p>
            Usa este código en la aplicación <strong>AgroControl</strong> para completar
            el restablecimiento de tu contraseña.
        </p>
        <div class="token">{{ $token }}</div>
        <div class="steps">
            <strong>Pasos:</strong><br>
            1. Abre AgroControl e inicia la recuperación.<br>
            2. Ingresa tu correo <strong>{{ $email ?? '' }}</strong>.<br>
            3. Copia el código de arriba y elige tu nueva contraseña.
        </div>
    </div>
</body>
</html>