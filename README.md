# DiscordPHP-login
DiscordPHP login
### Que es esto?
Es un fragmento de codigo destinado a interactuar con la api de Discord y permitir gestionar un inicio de sesion
en una web con solo tener un usuario de discord


### Consepto
En general el php se debe preconfigurar con la Array $Discord
llamar a login.php y $Discord almacenara los parametros otrorgados por [login.php](login.php)
que pueden ser usado luego para verificar el inicio de sesion y otras cosas

### Uso detallado
establece 
$Discord['C_ID'] = '??????'; //OAUTH2 CLIENT ID
$Discord['C_SE'] = '???????'; //OAUTH2 CLIENT SECRET
en login.php
esto pasa los parametros al [login.php](login.php)
```php
//application login setup
$Discord = array();
//$Discord['server_id'] = '?????????'; //Server ID. if state is 1, this set to 2 if is join, or 3 if not to the server
//$Discord['header'] = "NO";//Remove the Default HTML <header>

```
se llama al [login.php](login.php)
```php
require "login.php";
```

$Discord tendra este valor luego de llamar al login.php
```php
Array
(
    [C_ID] => ?????????????????
    [C_SE] => ?????????
    [header] => NO
)
```

luego se debe llamar al php principal con el parametro '?action=login' para iniciar el proceso de inicio de sesion
cuando este se complete se guardaran los datos como un COOKIE encriptado 
y $Discord devolvera los siguientes parametros
```php
Array
(
    [C_ID] => ?????????????
    [C_SE] => ?????????????????????????
    [header] => NO
    [state] => 2
    [user] => Array
        (
            [id] => FF4931783FFFFF30899FF
            [username] => Kronos2308
            [avatar] => https://cdn.discordapp.com/avatars/493178350123089930/b8abad5a7876410ea758d879a6393ceb.png?size=2048
            [discriminator] => 7751
            [public_flags] => 0
            [flags] => 0
            [locale] => es-ES
            [mfa_enabled] => 1
            [email] => kronos2308@gmail.com
            [verified] => 1
            [expire] => 1617836362
        )

)
```
$Discord['state'] va a devolver si estas inisiado sesion o no y $Discord['user'] para obtener informacion del usuario actual
tal como se indica el el [index.php](index.php)

### En la pagina de discord 
[Developer Portal](https://discord.com/developers)
debes crear una applicacion
![imagen](https://user-images.githubusercontent.com/36446521/113637600-e9518400-966c-11eb-8062-43675b4b046d.png)

Usa esos valores paa rellenar los ????? de antes
debes agregar tambien la ruta de login.php a la lista de redireccion o no funcionara es sensible a http y https




## este codigo a sido tomado la base de varias fuentes creditos sean dados
# ToDo
