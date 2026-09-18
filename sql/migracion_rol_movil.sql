USE web_pdv_db;

-- Modificar la columna 'rol' para admitir 'cajero_movil'
ALTER TABLE usuarios 
    MODIFY COLUMN rol ENUM('admin', 'cajero', 'cajero_movil') NOT NULL DEFAULT 'cajero' 
    COMMENT 'Rol del usuario en el sistema';
