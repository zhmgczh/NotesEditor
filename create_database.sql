create table words(
    word text  not null,
    character char(1) not null,
    checked boolean not null,
    primary key(word,character)
)