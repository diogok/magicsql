create table noticias (
        id int(11) ,
        titulo varchar(200),
        texto text,
        id_autores int(11),
        primary key(id)
            );

create table destaques (
        id int(11) ,
        titulo varchar(200),
        nome_autores int(11),
        primary key(id)
            );

create table comm (
        id int(11) ,
        com varchar(200),
        pref text,
        id_autores int(11),
        primary key(id)
            );

create table autores (
        id int(11) ,
        nome varchar(200),
        idade int(11),
        primary key(id)
        );
