**¿Como puedo levantar la pagina y utilizarlo?**

*¿como levanto la db en local?*
- tener PostgreSQL (pgadmin 4)
- tener activa las extenciones de PostgreSQL de php en el php.init
- crear una la base de datos en la aplicacion + la cuenta owner segun el .env.example
- pedir a la ia, segun las tablas que vayas a ocupar te de un comando para poner en quary tool que aparece dando click derecho en la db
- en la terminal de vscode ocupar  php -r "echo password_hash('Admin1234', PASSWORD_DEFAULT) . PHP_EOL;"  que lo que hace es hashear la contraseña que tenemos
- en quary tool de pgadmin 4 ocupar el los siguientes comando reemplazando el hash generado anteriormente por la parte destacada:

INSERT INTO usuario (nombre, correo, password_hash, rol)
VALUES ('Administrador', 'admin@canariapp.cl', '<HASH_GENERADO>', 'administrador');

- db levantado + admin generado

*¿como prendo el servidor?*
- crear un archivo .env en el backend que tenga exactamente lo mismo que el .env.example
- abre la terminal
- ¿estas en ..\Canarios-app\backend?, sino has  cd backend
- ejecuta  composer dump-autoload
- ejecuta  php -S localhost:8000 -t public
- server prendido

*¿que hago si no existe frontend?*
- se puede ocupar la extension postmate de vscode la cual te da la opcion de crear un http request
- una vez creado aparecera la opcion de poner los endpoints/url debera de poner  http://localhost:8000/api/v1  y poner "send"
- la parte izquierda a la antes mencionada pone "get", esa parte te da la opcion de cambiarla dependiendo de que vayas hacer, puedes ver que hace cada una en router.php
- para ingresar en  http://localhost:8000/api/login  se debe de poner la opcion donde el "get" elegir "post" y abajo la url elegir la parte de body esa parte funciona como un JSON ahi deben de poner lo siguiente y debera de dejarles acceder y les dara un token
{
    "correo": "admin@canariapp.cl",
    "password": "Admin1234"
}
- una ves copiado el token exacto buscaran a la izquierda de "body" una opcion que dice "auth" ahi en acceden al boton de al lado de 
"auth type" donde dice "no auth" y elijen "bearer token", les aparecera un espacio para escribir, ahi pegan el token que copiaron antes
- ahora tendran acceso a todos los endpoints como administrador
- si crean un criador tambien pueden acceder por su cuenta, solo que estaran mas restringidos, pero solo los criadores pueden acceder a  http://localhost:8000/api/v1/criador/perfil  donde se pueden ver su propio perfil y hacer cambios, como contraseña, etc., mientras que el admin puede hacerlo desde  http://localhost:8000/api/v1/admin/criadores/<id_criador>