# Reguły tworzenia kodu

Ten dokument definiuje obowiązujące zasady implementacyjne.

## Reguły ogólne

- preferujemy małe klasy o jednej odpowiedzialności
- unikamy anemicznego modelu domenowego
- nazwy klas i metod mają opisywać intencję biznesową
- logika biznesowa nie może być ukryta w kontrolerach, formularzach, commandach konsolowych ani repozytoriach ORM
- nie tworzymy klas `Helper`, `Util`, `Manager` bez mocnego uzasadnienia

## PHP

- używamy `declare(strict_types=1);` w kodzie aplikacyjnym
- preferujemy `final` dla klas, które nie są projektowane do dziedziczenia
- preferujemy niemutowalne value objecty
- unikamy publicznych mutatorów bez potrzeby biznesowej
- używamy jawnych typów argumentów, zwrotów i właściwości
- używamy wyjątków domenowych i aplikacyjnych zamiast zwracania niejawnych kodów błędów

## Symfony

- kontrolery mają być cienkie
- request nie trafia bezpośrednio do domeny
- kontroler deleguje do command albo query handlera
- konfiguracja frameworka ma pozostać poza domeną
- nie opieramy architektury na automagicznym skanowaniu bez jawnych kontraktów tam, gdzie logika jest istotna

## DDD

- agregat ma chronić swoje inwarianty
- stan agregatu zmieniamy tylko przez zachowania biznesowe
- value object ma reprezentować pojęcie domenowe, a nie tylko opakowanie na string
- repozytorium służy do pracy z agregatami, nie do przypadkowych zapytań raportowych
- read model nie zastępuje modelu domenowego

## CQRS

- każdy use case zapisujący ma osobny command
- każdy use case odczytowy ma osobny query
- command handler zwraca minimum potrzebnych danych
- query handler zwraca model dopasowany do UI lub API

## Testowanie

- logikę domenową testujemy testami jednostkowymi
- use case'y testujemy testami aplikacyjnymi
- integracje infrastrukturalne testujemy osobno
- nowe reguły biznesowe powinny powstawać razem z testem

## Integracja z HRnest

- do czasu końcowego etapu nie dodajemy klienta HRnest
- nie dodajemy kodu zależnego od dokumentacji HRnest do domeny
- jeśli potrzebujemy absencji, implementujemy wewnętrzny model i ewentualny mock adaptera
