=== SeoPilot ===
Contributors: radke447
Tags: seopilot, seopilot.pl
Requires at least: 3.6
Tested up to: 3.6.1
Stable tag: 1.1
License: GPLv2 or later

Wtyczka umożliwia wyświetlanie reklam systemu seopilot.pl

== Description ==

Wtyczka umożliwia wyświetlanie reklam systemu seopilot.pl przy użyciu widgetów (w przyszłości również shortcode'u i kodu PHP w motywach)

Funkcjonalność / Major features in SeoPilot 1.1:

* Możliwość umieszczania widgetów / Possibility to display ads via widget
* Możliwość zamieszczenia reklam przy użyciu shortcode: [seopilot is_test=0|1 charset="UTF-8"] (oraz w szablonach poprzez <?php echo do_shortcode('[seopilot is_test=0|1 charset="UTF-8"]'); ?>)
* Możliwość zmiany kodowania / Possibility to change encoding
* Możliwość włączania i wyłączania trybu testowego / Possibility to turn on/off test mode

== Installation ==

1. Zaintsaluj wtyczkę bezpośrednio w panelu administracyjnym swojego WordPress'a
2. Po instalacji zmień nazwę pliku bazy linków z "cf9fa261904dfe3b3c5960807693e1c1.links.db" w ten sposób, aby zawierała Twój identyfikator SeoPilot (identyfikator ten znajdziesz na stronie seopilot.pl, musisz być użytkownikiem seopilot.pl)
3. Zmień uprawnienia dla pliku bazy linków, tak, aby SeoPilot mógł go uaktualniać (np. 777)
4. Włącz wtyczkę i przejdź do ustawień (po lewej stronie w menu znajdziesz link SeoPilot)
5. Wpisz swój identyfikator SeoPilot i zapisz
6. Przejdź do widgetów i umieść widget SeoPilot tak gdzie chcesz
7. Zrobione.

Jeśli chcesz przetestować wtyczkę to ustaw w jej ustawieniach tryb testowy i na stronie z widget'em wyświetl źródło strony, znajdziesz w nim informacje o stanie rzeczy, również o błędach, jeśli się pojawią.

== Changelog ==

= 1.1 =
* Aktualizacja readme.txt
* Dodanie shortcode [seopilot is_test=0|1 charset="UTF-8"]. W plikach motywów można wykorzystać ten shortcode poprzez takie użycie: <?php echo do_shortcode('[seopilot is_test=0|1 charset="UTF-8"]'); ?>

= 1.0 =
* First version
