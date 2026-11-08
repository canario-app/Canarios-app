-- ---------- Catálogos ----------
CREATE TABLE color (
    id_color    SERIAL PRIMARY KEY,
    nombre      VARCHAR(50) NOT NULL,
    mutacion    VARCHAR(50)
);
 
CREATE TABLE alimento (
    id_alimento SERIAL PRIMARY KEY,
    nombre      VARCHAR(80) NOT NULL,
    tipo        VARCHAR(50)
);
 
CREATE TABLE competicion (
    id_competicion SERIAL PRIMARY KEY,
    nombre         VARCHAR(100) NOT NULL,
    concurso       VARCHAR(100),
    categoria      VARCHAR(50)
);
 
-- ---------- Canario ----------
CREATE TABLE canario (
    id_canario       SERIAL PRIMARY KEY,
    anillo           VARCHAR(30) NOT NULL UNIQUE,
    nombre           VARCHAR(80),
    sexo             CHAR(1) NOT NULL CHECK (sexo IN ('M','H')),  
    fecha_nacimiento DATE,
    disponibilidad   VARCHAR(30) DEFAULT 'disponible',
    id_color         INT REFERENCES color(id_color)
);
 
-- ---------- Pareja y Cruce ----------
CREATE TABLE pareja (
    id_pareja       SERIAL PRIMARY KEY,
    id_macho        INT NOT NULL REFERENCES canario(id_canario),
    id_hembra       INT NOT NULL REFERENCES canario(id_canario),
    fecha_formacion DATE,
    estado          VARCHAR(30) DEFAULT 'activa',
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
 
-- Origina: un cruce (1) origina muchos canarios (N)
ALTER TABLE canario
    ADD COLUMN id_cruce INT REFERENCES cruce(id_cruce);
 
-- ---------- Competición (N:M con Canario) ----------
CREATE TABLE canario_competicion (
    id_canario     INT NOT NULL REFERENCES canario(id_canario),
    id_competicion INT NOT NULL REFERENCES competicion(id_competicion),
    fecha_obtenido DATE,
    puesto         INT CHECK (puesto > 0),
    PRIMARY KEY (id_canario, id_competicion)
);
 
-- ---------- Alimentación (N:M con Canario) ----------
CREATE TABLE consumo_alimento (
    id_consumo  SERIAL PRIMARY KEY,
    id_canario  INT NOT NULL REFERENCES canario(id_canario),
    id_alimento INT NOT NULL REFERENCES alimento(id_alimento),
    cantidad    NUMERIC(8,2) CHECK (cantidad > 0),
    hora        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
 
-- ---------- Salud ----------
CREATE TABLE monitoreo_salud (
    id_monitoreo_salud SERIAL PRIMARY KEY,
    id_canario         INT NOT NULL REFERENCES canario(id_canario),
    fecha              DATE NOT NULL DEFAULT CURRENT_DATE,
    peso_g             NUMERIC(6,2) CHECK (peso_g > 0),
    observaciones      TEXT
);
 
-- ---------- Árbol genealógico ----------
CREATE TABLE arbol_genealogico (
    id_arbol       SERIAL PRIMARY KEY,
    id_canario     INT NOT NULL UNIQUE REFERENCES canario(id_canario),
    id_padre       INT REFERENCES canario(id_canario),
    id_madre       INT REFERENCES canario(id_canario),
    id_cruce       INT REFERENCES cruce(id_cruce),
    generacion     INT CHECK (generacion >= 0),
    orden_registro INT,
    fecha_registro DATE DEFAULT CURRENT_DATE,
    tipo_origen    VARCHAR(30)
);
 
-- ---------- Simulación genética ----------
CREATE TABLE simulacion (
    id_simulacion        SERIAL PRIMARY KEY,
    id_macho             INT NOT NULL REFERENCES canario(id_canario),
    id_hembra            INT NOT NULL REFERENCES canario(id_canario),
    id_arbol_genealogico INT REFERENCES arbol_genealogico(id_arbol),
    fecha                DATE DEFAULT CURRENT_DATE,
    CONSTRAINT ck_sim_distintos CHECK (id_macho <> id_hembra)
);
 
CREATE TABLE simulacion_resultado (
    id_resultado  SERIAL PRIMARY KEY,
    id_simulacion INT NOT NULL REFERENCES simulacion(id_simulacion) ON DELETE CASCADE,
    id_color      INT NOT NULL REFERENCES color(id_color),
    sexo_cria     CHAR(1) CHECK (sexo_cria IN ('M','H')),
    probabilidad  NUMERIC(5,2) CHECK (probabilidad BETWEEN 0 AND 100)
);
 
-- ---------- Índices en llaves foráneas ----------
CREATE INDEX idx_canario_color     ON canario(id_color);
CREATE INDEX idx_canario_cruce     ON canario(id_cruce);
CREATE INDEX idx_pareja_macho      ON pareja(id_macho);
CREATE INDEX idx_pareja_hembra     ON pareja(id_hembra);
CREATE INDEX idx_cruce_pareja      ON cruce(id_pareja);
CREATE INDEX idx_consumo_canario   ON consumo_alimento(id_canario);
CREATE INDEX idx_salud_canario     ON monitoreo_salud(id_canario);
CREATE INDEX idx_arbol_padre       ON arbol_genealogico(id_padre);
CREATE INDEX idx_arbol_madre       ON arbol_genealogico(id_madre);
CREATE INDEX idx_sim_resultado_sim ON simulacion_resultado(id_simulacion);
