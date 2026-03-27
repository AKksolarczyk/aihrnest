# Cel produktu

Smart Desk & Rooms Scheduler to aplikacja webowa do:
- rezerwacji biurek
- rezerwacji sal konferencyjnych
- przeglądania dostępności zasobów w widoku dnia
- anulowania istniejących rezerwacji
- prezentacji zasobów na interaktywnej mapie biura

## Główne założenia biznesowe

- użytkownik widzi dostępność biurek i sal dla wybranego dnia
- użytkownik może zarezerwować biurko albo salę, jeśli zasób jest dostępny
- użytkownik może anulować swoją rezerwację
- system ma wspierać konfigurację przestrzeni biurowej, pięter, pomieszczeń i zasobów
- system ma wspierać absencje pracowników, które wpływają na dostępność zasobów
- system moze wspierac waitliste na zajete zasoby
- system moze wspierac rezerwacje cykliczne
- system moze wspierac zglaszanie problemow z zasobami
- system moze wspierac wyszukiwanie osob obecnych w biurze
- system ma dostarczac raporty operacyjne dla administratora

## Założenia bieżącego etapu

Na obecnym etapie:
- nie integrujemy się z HRnest
- nie implementujemy zewnętrznych adapterów do systemów HR
- traktujemy absencje jako element wewnętrznego modelu domenowego
- dopuszczamy mock lub ręczne zarządzanie absencjami na czas budowy rdzenia systemu

## Priorytet implementacyjny

Najpierw budujemy:
- model domeny biurek, sal i rezerwacji
- reguły dostępności
- reguły anulowania i zwalniania zasobów
- model absencji niezależny od zewnętrznego dostawcy
- interfejsy aplikacyjne i kontrakty pod późniejszą integrację

Na końcu budujemy:
- integrację z HRnest
- synchronizację absencji z systemem zewnętrznym
- automatyczne zwalnianie rezerwacji po danych z HRnest
