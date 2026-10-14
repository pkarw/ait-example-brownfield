<?php
if (get_option('taskflow_seeded')) return;
$rows = [
 ['Zaprojektować nową stronę główną','Nowa strona www','high','progress',0,'AK','Przygotować projekt sekcji hero, korzyści i formularza kontaktowego.'],
 ['Zaktualizować wtyczki WordPress','Utrzymanie strony','high','todo',0,'MK','Wykonać kopię zapasową i sprawdzić kompatybilność wtyczek.'],
 ['Przygotować wpis na bloga','Content & SEO','medium','todo',1,'AK','Temat: dobre praktyki projektowania stron internetowych.'],
 ['Sprawdzić formularz kontaktowy','Nowa strona www','medium','progress',1,'JK','Przetestować walidację oraz dostarczanie wiadomości.'],
 ['Zoptymalizować zdjęcia produktów','Sklep internetowy','low','todo',2,'MK','Zmniejszyć wagę zdjęć i uzupełnić teksty alternatywne.'],
 ['Dodać meta opisy podstron','Content & SEO','medium','todo',3,'AK','Uzupełnić opisy najważniejszych podstron.'],
 ['Skonfigurować Google Analytics','Nowa strona www','high','done',-1,'JK','Połączyć witrynę z GA4 i sprawdzić zdarzenia.'],
 ['Wykonać kopię zapasową strony','Utrzymanie strony','low','done',-1,'MK','Archiwum plików i bazy danych.'],
 ['Przetestować proces zakupowy','Sklep internetowy','high','todo',4,'JK','Sprawdzić koszyk, płatności i wiadomości transakcyjne.'],
 ['Przygotować plan publikacji','Content & SEO','low','todo',5,'AK','Rozpisać harmonogram na najbliższy miesiąc.'],
 ['Poprawić widok mobilny menu','Nowa strona www','medium','progress',6,'AK','Dopracować nawigację na telefonach.'],
 ['Uporządkować bibliotekę mediów','Utrzymanie strony','low','todo',7,'MK','Przejrzeć nieużywane załączniki.']
];
foreach ($rows as $r) {
 $id = wp_insert_post(['post_type'=>'taskflow_task','post_status'=>'publish','post_title'=>$r[0],'post_content'=>$r[6]]);
 foreach (['project'=>$r[1],'priority'=>$r[2],'status'=>$r[3],'due'=>date('Y-m-d',strtotime(($r[4]>=0?'+':'').$r[4].' days')),'assignee'=>$r[5]] as $k=>$v) update_post_meta($id,'_tf_'.$k,$v);
}
update_option('taskflow_seeded',1);
