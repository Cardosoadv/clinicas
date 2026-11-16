<?php
/** @var array $client */
/** @var array $p */
/** @var string|null $erro */
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Autorizar acesso</title>
<style>
  body{font-family:system-ui,sans-serif;background:#f4f6f8;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:16px}
  .card{background:#fff;max-width:420px;width:100%;border-radius:12px;padding:28px;box-shadow:0 2px 12px rgba(0,0,0,.08)}
  h1{font-size:1.2rem;margin:0 0 12px} p{color:#444;font-size:.92rem;line-height:1.45}
  label{display:block;font-size:.85rem;margin:14px 0 4px} input[type=email],input[type=password]{width:100%;box-sizing:border-box;padding:10px;border:1px solid #c5ccd3;border-radius:8px;font-size:1rem}
  .erro{background:#fdecea;color:#b3261e;padding:10px;border-radius:8px;font-size:.88rem;margin-top:12px}
  .acoes{display:flex;gap:8px;justify-content:flex-end;margin-top:22px}
  button{padding:10px 16px;border-radius:8px;border:1px solid #c5ccd3;background:#fff;font-size:.95rem;cursor:pointer}
  button.primario{background:#1f6feb;border-color:#1f6feb;color:#fff}
</style>
</head>
<body>
<form class="card" method="post" action="<?= esc(site_url('oauth/authorize'), 'attr') ?>">
  <h1>Autorizar acesso de IA externa</h1>
  <p><strong><?= esc($client['client_name'] ?: 'Um cliente MCP externo') ?></strong> quer se conectar ao sistema da clínica em seu nome,
     para incluir clientes, pacientes e agendamentos. Entre com seu usuário do sistema para permitir. Você pode revogar o acesso depois.</p>
  <?php if (!empty($erro)): ?><div class="erro" role="alert"><?= esc($erro) ?></div><?php endif; ?>
  <?php foreach ($p as $nome => $valor): ?>
    <input type="hidden" name="<?= esc($nome, 'attr') ?>" value="<?= esc($valor, 'attr') ?>">
  <?php endforeach; ?>
  <label for="email">E-mail</label>
  <input id="email" type="email" name="email" autocomplete="username" required>
  <label for="password">Senha</label>
  <input id="password" type="password" name="password" autocomplete="current-password" required>
  <div class="acoes">
    <button type="submit" name="decisao" value="negar" formnovalidate>Negar</button>
    <button type="submit" name="decisao" value="permitir" class="primario">Permitir acesso</button>
  </div>
</form>
</body>
</html>
