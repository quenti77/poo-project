create table users
(
    id varchar(32)  not null primary key,
    group_id varchar(32)  not null,
    name varchar(255) not null,
    email varchar(255) not null,
    email_verified_at datetime null,
    password varchar(255) not null,
    token varchar(255) null,
    created_at datetime not null,
    updated_at datetime not null,
    constraint users_email_uindex unique (email),
    constraint users_groups_id_fk
        foreign key (group_id) references `groups` (id)
);
