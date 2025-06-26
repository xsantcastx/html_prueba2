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


CREATE TABLE users_login (
    idLogin INT AUTO_INCREMENT PRIMARY KEY,
    idUser INT NOT NULL UNIQUE,
    usuario VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, 
    rol ENUM('admin', 'user') NOT NULL,
    FOREIGN KEY (idUser) REFERENCES users_data(idUser) ON DELETE CASCADE
);


CREATE TABLE citas (
    idCita INT AUTO_INCREMENT PRIMARY KEY,
    idUser INT NOT NULL,
    fecha_cita DATETIME NOT NULL,
    motivo_cita TEXT,
    FOREIGN KEY (idUser) REFERENCES users_data(idUser) ON DELETE CASCADE
);


CREATE TABLE noticias (
    idNoticia INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL UNIQUE,
    imagen VARCHAR(255) NOT NULL,
    texto TEXT NOT NULL,
    fecha DATE NOT NULL,
    idUser INT NOT NULL, 
    FOREIGN KEY (idUser) REFERENCES users_data(idUser) ON DELETE CASCADE
);
