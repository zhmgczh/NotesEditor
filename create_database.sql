drop table words;
create table words(
    word text not null,
    character char(1) not null,
    checked boolean not null default false,
    primary key(word,character)
);
delete from words;
delete from words where word='鳧雁';
insert or ignore into words (word,character) values ('反撲','撲');
insert or ignore into words (word,character) values ('猛撲','撲');
select * from words;
select character from words where word='撲向';
select word,character from words where checked=false;
update or ignore words set checked=not checked where word='撲向' and character='撲';