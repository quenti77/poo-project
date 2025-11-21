--
-- groups
insert into `groups` (id, name, level, created_at, updated_at)
values ('01K98GP6A9MXYRZNJ8S7VRTV9Q', 'admin', 10, '2025-11-04 23:30:00', '2025-11-04 23:30:00');

-- user: quentin
-- mdp: root
insert into `users` (id, group_id, name, email, email_verified_at, password, token, created_at, updated_at)
values ('01K98GPYC74790VNQ4VBR7Z62K', '01K98GP6A9MXYRZNJ8S7VRTV9Q', 'quentin', 'quentin@tuto.local', '2025-11-04 23:30:00', '$2y$14$4BQ2KgvSNnT.reBgqsRNyuN1gJM443ql6HdBqaMknW5pFczskrvGa', null, '2025-11-04 23:30:00', '2025-11-04 23:30:00');
