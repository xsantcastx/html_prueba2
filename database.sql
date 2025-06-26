-- SQL schema for the database

-- Table: users_data
-- Stores personal information of users
CREATE TABLE users_data (
    idUser INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    apellidos VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    telefono VARCHAR(255) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    direccion TEXT,
    sexo ENUM('masculino', 'femenino', 'otro')
);

-- Table: users_login
-- Stores login information for registered users
CREATE TABLE users_login (
    idLogin INT AUTO_INCREMENT PRIMARY KEY,
    idUser INT NOT NULL UNIQUE,
    usuario VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Will be stored encrypted
    rol ENUM('admin', 'user') NOT NULL,
    FOREIGN KEY (idUser) REFERENCES users_data(idUser) ON DELETE CASCADE
);

-- Table: citas
-- Stores appointment information
CREATE TABLE citas (
    idCita INT AUTO_INCREMENT PRIMARY KEY,
    idUser INT NOT NULL,
    fecha_cita DATETIME NOT NULL,
    motivo_cita TEXT,
    FOREIGN KEY (idUser) REFERENCES users_data(idUser) ON DELETE CASCADE
);

-- Table: noticias
-- Stores news articles written by administrators
CREATE TABLE noticias (
    idNoticia INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL UNIQUE,
    imagen VARCHAR(255) NOT NULL, -- Path to the image file
    texto TEXT NOT NULL,
    fecha DATE NOT NULL,
    idUser INT NOT NULL, -- User who created the news (admin)
    FOREIGN KEY (idUser) REFERENCES users_data(idUser) ON DELETE CASCADE
);
