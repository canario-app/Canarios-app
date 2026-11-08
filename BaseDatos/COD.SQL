BEGIN;

DROP TABLE IF EXISTS
    simulacion_resultado, simulacion, monitoreo_salud, consumo_alimento,
    cruce_competicion, competicion, alimento, arbol_genealogico, cria,
    nidada, cruce, pareja, titulo, solicitud_correccion, auditoria,
    respaldo, modo_operacion, jaula, canario, color, variedad, club,
    criador, mutacion, usuario CASCADE;


CREATE TABLE usuario (
    id_usuario     SERIAL PRIMARY KEY,
    nombre         VARCHAR(100) NOT NULL,
    correo         VARCHAR(150) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    rol            VARCHAR(15)  NOT NULL CHECK (rol IN ('criador','administrador')),
    fecha_creacion TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE criador (
    id_criador     SERIAL PRIMARY KEY,
    id_usuario     INT NOT NULL UNIQUE REFERENCES usuario(id_usuario),
    numero_socio   VARCHAR(5),
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE club (
    id_club SERIAL PRIMARY KEY,
    sigla   VARCHAR(6) NOT NULL UNIQUE CHECK (sigla ~ '^[A-Z]{2,6}$')
);

CREATE TABLE variedad (
    id_variedad SERIAL PRIMARY KEY,
    codigo      VARCHAR(10)  NOT NULL UNIQUE,
    nombre      VARCHAR(150) NOT NULL,
    tipo        CHAR(2)      NOT NULL CHECK (tipo IN ('CC','CP')),
    serie       VARCHAR(50)
);

CREATE TABLE color (
    id_color SERIAL PRIMARY KEY,
    nombre   VARCHAR(100) NOT NULL,
    mutacion VARCHAR(100)
);

CREATE TABLE mutacion (
    id_mutacion   SERIAL PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL UNIQUE,
    tipo_herencia VARCHAR(50)  NOT NULL
);


CREATE TABLE jaula (
    id_jaula     SERIAL PRIMARY KEY,
    numero_jaula VARCHAR(20) NOT NULL,
    id_criador   INT NOT NULL REFERENCES criador(id_criador)
);

CREATE TABLE canario (
    id_canario        SERIAL PRIMARY KEY,
    anillo            VARCHAR(20) NOT NULL,
    nombre            VARCHAR(100),
    sexo              CHAR(1) CHECK (sexo IN ('M','H')),
    fecha_nacimiento  DATE,
    disponibilidad    VARCHAR(20) NOT NULL DEFAULT 'disponible',
    id_color          INT REFERENCES color(id_color),
    numero_socio      VARCHAR(5),
    temporada         INT,
    id_club           INT REFERENCES club(id_club),
    id_variedad       INT REFERENCES variedad(id_variedad),
    id_criador        INT NOT NULL REFERENCES criador(id_criador),
    requiere_revision BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE pareja (
    id_pareja       SERIAL PRIMARY KEY,
    id_macho        INT NOT NULL REFERENCES canario(id_canario),
    id_hembra       INT NOT NULL REFERENCES canario(id_canario),
    id_jaula        INT NOT NULL REFERENCES jaula(id_jaula),
    fecha_formacion DATE,
    estado          VARCHAR(15) NOT NULL DEFAULT 'activa'
                    CHECK (estado IN ('activa','finalizada','anulada')),
    CONSTRAINT ck_pareja_distintos CHECK (id_macho <> id_hembra)
);

CREATE TABLE cruce (
    id_cruce          SERIAL PRIMARY KEY,
    id_pareja         INT NOT NULL REFERENCES pareja(id_pareja),
    fecha_apareamiento DATE,
    fecha_postura     DATE,
    fecha_eclosion    DATE,
    huevos_total      INT CHECK (huevos_total >= 0)
);

CREATE TABLE nidada (
    id_nidada        SERIAL PRIMARY KEY,
    id_cruce         INT  NOT NULL REFERENCES cruce(id_cruce),
    fecha_nacimiento DATE
);

CREATE TABLE cria (
    id_cria         SERIAL PRIMARY KEY,
    id_canario      INT NOT NULL UNIQUE REFERENCES canario(id_canario),
    id_nidada       INT NOT NULL REFERENCES nidada(id_nidada),
    es_portadora    BOOLEAN NOT NULL DEFAULT FALSE,
    factor_genetico INT REFERENCES mutacion(id_mutacion),
    reservada       BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE arbol_genealogico (
    id_arbol       SERIAL PRIMARY KEY,
    id_canario     INT NOT NULL UNIQUE REFERENCES canario(id_canario),
    id_padre       INT REFERENCES canario(id_canario),
    id_madre       INT REFERENCES canario(id_canario),
    id_cruce       INT REFERENCES cruce(id_cruce),
    generacion     INT,
    orden_registro INT,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo_origen    VARCHAR(30),
    CONSTRAINT ck_arbol_padre_distinto CHECK (id_padre IS NULL OR id_padre <> id_canario),
    CONSTRAINT ck_arbol_madre_distinta CHECK (id_madre IS NULL OR id_madre <> id_canario)
);

CREATE TABLE titulo (
    id_titulo  SERIAL PRIMARY KEY,
    id_canario INT NOT NULL REFERENCES canario(id_canario),
    nombre     VARCHAR(100) NOT NULL,
    fecha      DATE
);

CREATE TABLE solicitud_correccion (
    id_solicitud     SERIAL PRIMARY KEY,
    id_canario       INT NOT NULL REFERENCES canario(id_canario),
    id_criador       INT NOT NULL REFERENCES criador(id_criador),
    dato_a_corregir  VARCHAR(50) NOT NULL,
    motivo           TEXT,
    estado           VARCHAR(15) NOT NULL DEFAULT 'pendiente'
                     CHECK (estado IN ('pendiente','aprobada','rechazada','cancelada')),
    fecha_solicitud  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE alimento (
    id_alimento SERIAL PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    tipo        VARCHAR(50)
);

CREATE TABLE consumo_alimento (
    id_consumo  SERIAL PRIMARY KEY,
    id_canario  INT NOT NULL REFERENCES canario(id_canario),
    id_alimento INT NOT NULL REFERENCES alimento(id_alimento),
    cantidad    NUMERIC(8,2),
    hora        TIME
);

CREATE TABLE competicion (
    id_competicion SERIAL PRIMARY KEY,
    nombre         VARCHAR(100) NOT NULL,
    concurso       VARCHAR(100),
    categoria      VARCHAR(50)
);

CREATE TABLE cruce_competicion (
    id_cruce_competicion SERIAL PRIMARY KEY,
    id_cruce             INT NOT NULL REFERENCES cruce(id_cruce),
    id_competicion       INT NOT NULL REFERENCES competicion(id_competicion),
    fecha_obtenido       DATE,
    puesto               INT
);

CREATE TABLE simulacion (
    id_simulacion       SERIAL PRIMARY KEY,
    id_macho            INT REFERENCES canario(id_canario),
    id_hembra           INT REFERENCES canario(id_canario),
    id_arbol_genealogico INT REFERENCES arbol_genealogico(id_arbol),
    id_mutacion         INT REFERENCES mutacion(id_mutacion),
    genotipo_macho      VARCHAR(100),
    genotipo_hembra     VARCHAR(100),
    fecha               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE simulacion_resultado (
    id_resultado       SERIAL PRIMARY KEY,
    id_simulacion      INT NOT NULL REFERENCES simulacion(id_simulacion) ON DELETE CASCADE,
    id_color           INT REFERENCES color(id_color),
    sexo_cria          CHAR(1) CHECK (sexo_cria IN ('M','H')),
    genotipo_resultado VARCHAR(100),
    probabilidad       NUMERIC(6,5) CHECK (probabilidad BETWEEN 0 AND 1)
);

CREATE TABLE monitoreo_salud (
    id_monitoreo_salud SERIAL PRIMARY KEY,
    id_canario         INT NOT NULL REFERENCES canario(id_canario),
    fecha              DATE NOT NULL,
    peso_g             NUMERIC(6,2),
    observaciones      TEXT
);

CREATE TABLE auditoria (
    id_auditoria           BIGSERIAL PRIMARY KEY,
    id_usuario             INT REFERENCES usuario(id_usuario),
    fecha_hora             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accion                 VARCHAR(50) NOT NULL,
    entidad                VARCHAR(50),
    id_registro            INT,
    valor_anterior         TEXT,
    valor_nuevo            TEXT,
    motivo                 TEXT,
    id_criador_solicitante INT REFERENCES criador(id_criador)
);

CREATE TABLE respaldo (
    id_respaldo SERIAL PRIMARY KEY,
    fecha_hora  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    archivo     VARCHAR(255) NOT NULL,
    id_usuario  INT REFERENCES usuario(id_usuario)
);

CREATE TABLE modo_operacion (
    id_modo             SERIAL PRIMARY KEY,
    modo                VARCHAR(15) NOT NULL
                        CHECK (modo IN ('normal','mantenimiento','emergencia')),
    fecha_activacion    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_desactivacion TIMESTAMP,
    id_usuario          INT REFERENCES usuario(id_usuario),
    diagnostico         TEXT,
    acciones_recuperacion TEXT
);

CREATE INDEX idx_criador_usuario          ON criador(id_usuario);
CREATE INDEX idx_jaula_criador            ON jaula(id_criador);
CREATE INDEX idx_canario_color            ON canario(id_color);
CREATE INDEX idx_canario_club             ON canario(id_club);
CREATE INDEX idx_canario_variedad         ON canario(id_variedad);
CREATE INDEX idx_canario_criador          ON canario(id_criador);
CREATE INDEX idx_pareja_macho             ON pareja(id_macho);
CREATE INDEX idx_pareja_hembra            ON pareja(id_hembra);
CREATE INDEX idx_pareja_jaula             ON pareja(id_jaula);
CREATE INDEX idx_cruce_pareja             ON cruce(id_pareja);
CREATE INDEX idx_nidada_cruce             ON nidada(id_cruce);
CREATE INDEX idx_cria_canario             ON cria(id_canario);
CREATE INDEX idx_cria_nidada              ON cria(id_nidada);
CREATE INDEX idx_cria_mutacion            ON cria(factor_genetico);
CREATE INDEX idx_arbol_canario            ON arbol_genealogico(id_canario);
CREATE INDEX idx_arbol_padre              ON arbol_genealogico(id_padre);
CREATE INDEX idx_arbol_madre              ON arbol_genealogico(id_madre);
CREATE INDEX idx_arbol_cruce              ON arbol_genealogico(id_cruce);
CREATE INDEX idx_titulo_canario           ON titulo(id_canario);
CREATE INDEX idx_solicitud_canario        ON solicitud_correccion(id_canario);
CREATE INDEX idx_solicitud_criador        ON solicitud_correccion(id_criador);
CREATE INDEX idx_consumo_canario          ON consumo_alimento(id_canario);
CREATE INDEX idx_consumo_alimento         ON consumo_alimento(id_alimento);
CREATE INDEX idx_cruce_competicion_cruce  ON cruce_competicion(id_cruce);
CREATE INDEX idx_cruce_competicion_comp   ON cruce_competicion(id_competicion);
CREATE INDEX idx_simulacion_macho         ON simulacion(id_macho);
CREATE INDEX idx_simulacion_hembra        ON simulacion(id_hembra);
CREATE INDEX idx_simulacion_arbol         ON simulacion(id_arbol_genealogico);
CREATE INDEX idx_simulacion_mutacion      ON simulacion(id_mutacion);
CREATE INDEX idx_simresultado_simulacion  ON simulacion_resultado(id_simulacion);
CREATE INDEX idx_simresultado_color       ON simulacion_resultado(id_color);
CREATE INDEX idx_monitoreo_canario        ON monitoreo_salud(id_canario);
CREATE INDEX idx_auditoria_usuario        ON auditoria(id_usuario);
CREATE INDEX idx_auditoria_criador        ON auditoria(id_criador_solicitante);
CREATE INDEX idx_auditoria_fecha          ON auditoria(fecha_hora);
CREATE INDEX idx_respaldo_usuario         ON respaldo(id_usuario);
CREATE INDEX idx_modo_usuario             ON modo_operacion(id_usuario);

COMMIT;
