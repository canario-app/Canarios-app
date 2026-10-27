# Requisitos iniciales (SOLO LO MODIFICA EL MP Y QUIEN EL AUTORICE EN CASO ESPECIAL)

Borrador basado en el pedido del cliente. MP valida prioridades y criterios con el cliente. La división en etapas es una propuesta y no elimina requisitos.

| ID  | Función                         | Comprobación básica                                                                                            | Etapa propuesta |
| --- | ---                             | ---                                                                                                            |---|
| R01 | Registro por anilla             | Registrar y buscar por identificador completo; rechazar duplicados.                                            | - |
| R02 | Disponibilidad y color          | Guardar y consultar el estado y color acordados.                                                               | - |
| R03 | Nacimiento, generación y padres | Consultar estos datos según la definición acordada de generación.                                              | - |
| R04 | Identificación de parejas       | Guardar una pareja de dos canarios distintos y consultar sus integrantes.                                      | - |
| R05 | Cruces y fechas de apareamiento | Registrar un cruce fechado vinculado a una pareja.                                                             | - |
| R06 | Crías e historial               | Vincular crías a progenitores y consultar la descendencia.                                                     | - |
| R07 | Títulos de ganador              | Guardar concurso, título y fecha; mostrarlos en la ficha.                                                      | - |
| R08 | Monitoreo manual                | Registrar observaciones fechadas y consultar su historial.                                                     | - |
| R09 | Horarios de comida              | Guardar y consultar horarios; confirmar si incluye notificaciones.                                             | - |
| R10 | Posibles parejas                | Mostrar sugerencias y motivo según reglas acordadas con el cliente.                                            | - |
| R11 | Simulación de resultados        | Mostrar estimaciones según datos y reglas genéticas validadas; si faltan datos, indicarlo.                     | - |
| R12 | Monitoreo de sonido             | Definir captura, equipo y objetivo; validar el método antes de presentar conclusiones sobre el estado del ave. | - |

## Decisiones pendientes para la primera reunión (ejemplos para el MP)

- Qué significan disponibilidad, generación y monitoreo para el cliente.
- Cómo se compone el identificador completo del anillo; no asumir que un número corto es único.
- Cómo se identifican temporalmente las crías que aún no tienen anilla.
- Qué usuarios usarán la app, qué permisos requieren y si será web o móvil.
- Tecnologías, fechas y alcance aceptado de cada entrega.
- Reglas para sugerir parejas y datos necesarios para simular cruces. No inventar porcentajes basados solo en el color visible.
- Si los horarios de alimentación son solo registro o también recordatorios; el horario lo define el usuario.
- Si sonido requiere micrófono, grabación continua o archivos; cómo evaluar el resultado. No tratarlo como diagnóstico automático sin validación.
