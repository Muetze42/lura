<?php

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../../vendor/autoload.php';

$request = Request::createFromGlobals();
$errors = null;

if ($request->isMethod('POST')) {
    $loader = new FileLoader(new Filesystem(), '');
    $translator = new Translator($loader, 'en');
    $factory = new Factory($translator, new Container());
    $messages = include __DIR__.'/lang/en/validation.php';

    $rules = array(
        'name' => ['required', 'min:3', 'max:20'],
        'password' => ['required', 'min:5', 'max:60']
    );

    $validator = $factory->make($request->request->all(), $rules, $messages);
    if ($validator->fails()) {
        $errors = $validator->errors();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Validation</title>
</head>
<body>
<?php
if ($errors && $errors->any()) { ?>
<div style="padding: .25rem; background-color: #b91c1c;color: white; margin-bottom: 1rem">
    Errors
    <ul>
        <?php foreach ($errors->all() as $error) { ?>
            <li><?= $error ?></li>
        <?php } ?>
    </ul>
</div>
<?php } ?>
<form method="post">
    Name <input type="text" name="name"><br>
    Password <input type="text" name="password"><br>
    <input type="submit">
</form>
</body>
</html>

