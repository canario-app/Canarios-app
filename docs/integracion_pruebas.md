# Pruebas de integración

Integridad registrara las pruebas entre equipos; cada equipo prueba y corrige sus propias funciones. Anotar versión o commit y resultados cuando se ejecute una prueba.

Usara datos ficticios y la pantalla conectada al backend y a una BD de prueba. Las capturas por separado no demuestran integración.

| Caso | Acción                                                         | Resultado esperado                                                     | Estado    |
|------|----------------------------------------------------------------|------------------------------------------------------------------------|-----------|
| P01  | Registrar un canario por anilla y volver a consultar.          | La ficha conserva los datos guardados.                                 | Pendiente |
| P02  | Repetir el mismo identificador completo de anilla.             | Error claro y ningún duplicado en BD.                                  | Pendiente |
| P03  | Enviar datos obligatorios incompletos.                         | El backend rechaza y la pantalla explica qué falta.                    | Pendiente |
| P04  | Crear pareja y registrar cruce con fecha.                      | La pareja y la fecha aparecen en su historial.                         | Pendiente |
| P05  | Registrar una cría y sus progenitores.                         | Se consulta desde la ficha de la cría y la descendencia de sus padres. | Pendiente |
| P06  | Guardar color, disponibilidad, nacimiento, generación y título.| La ficha muestra los valores de acuerdo con las reglas aceptadas.      | Pendiente |
| P07  | Registrar observación y horario de comida.                     | Se guardan y consultan correctamente.                                  | Pendiente |
| P08  | Enviar referencias inválidas o el propio canario como padre.   | El backend y la BD evitan vínculos inválidos.                          | Pendiente |
| P09  | Crear la BD desde cero con las migraciones documentadas.       | Un compañero puede ejecutar el proyecto siguiendo los README.          | Pendiente |
| P10  | Probar errores de conexión.                                    | La pantalla informa el error sin afirmar que guardó correctamente.     | Pendiente |

Al incorporar sugerencias, simulación o sonido, se agregaran casos acordados con el cliente, incluidos datos insuficientes y límites del resultado.

## Registro de ejecución para cada prueba

- Fecha:
- Commit o versión:
- Casos ejecutados:
- Resultado esperado / observado:
- Evidencia:
- Error asociado, responsable y estado:

## Uso del documento

1. Se probara un flujo cuando las partes necesarias estén integradas.
2. Se anotara qué funcionó y qué falló.
3. Se avisara al equipo correspondiente si hay un problema.
4. Se Repetira la prueba después de la corrección.
5. Se agregara al documento de pruebas hechas con su resultado
