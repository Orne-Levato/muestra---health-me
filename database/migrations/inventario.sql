-- Tabla de inventario de medicamentos.
-- Se vincula con la tabla `usuario` (columna `id`).
-- Si al ejecutarlo da error 1215/150, usuario.id tiene otro tipo (ej. INT UNSIGNED o BIGINT):
-- cambiar el tipo de usuario_id para que sea idéntico (se ve en phpMyAdmin > usuario > Estructura).

CREATE TABLE inventario_medicamentos (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id     INT NOT NULL,
  nombre         VARCHAR(100) NOT NULL,
  dosis_por_toma DECIMAL(5,2) NOT NULL DEFAULT 1,   -- pastillas por toma (admite 0.5)
  tomas_por_dia  TINYINT UNSIGNED NOT NULL DEFAULT 1,
  stock_base     DECIMAL(8,2) NOT NULL DEFAULT 0,   -- pastillas que había en fecha_base
  fecha_base     DATE NOT NULL,                     -- día en que se cargó/corrigió el stock
  umbral_dias    TINYINT UNSIGNED NOT NULL DEFAULT 3, -- avisar si alcanzan para <= N días
  ultimo_aviso   DATE NULL,                         -- último día que se mandó el mail
  creado_en      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_usuario (usuario_id),
  CONSTRAINT fk_inv_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuario(id) ON DELETE CASCADE
);
