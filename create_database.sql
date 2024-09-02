drop table words;
create table words(
    word text not null,
    character char(1) not null,
    checked boolean not null default false,
    primary key(word,character)
);
create table status(
    id int primary key not null,
    time bigint not null,
    entry_database longtext not null,
    all_words longtext not null
);
delete from words;
delete from words where word='鳧雁';
insert or ignore into words (word,character) values ('反撲','撲');
insert or ignore into words (word,character) values ('猛撲','撲');
select * from words where word='胡說';
update or ignore words set checked=not checked where word='胡說' and character='胡';
select * from status;
select character,checked from words where word='撲向' order by checked,character;
select word,character from words where checked=false order by character,word;
update or ignore words set checked=not checked where word='撲向' and character='撲';
update or ignore words set checked=true where word='撲向' and character='撲';