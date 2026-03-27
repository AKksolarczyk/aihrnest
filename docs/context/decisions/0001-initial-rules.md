# ADR 0001: Reguły początkowe projektu

## Status

Accepted

## Kontekst

Projekt startuje jako nowa aplikacja Symfony dla rezerwacji biurek i sal konferencyjnych. Na starcie ważniejsze jest zbudowanie poprawnego rdzenia domenowego niż szybka integracja z systemami zewnętrznymi.

## Decyzja

Przyjmujemy następujące decyzje początkowe:
- kod powstaje w PHP z użyciem Symfony
- architektura domenowa ma być zgodna z DDD
- warstwa aplikacyjna ma respektować CQRS
- domena pozostaje niezależna od Symfony, Doctrine i HRnest
- integrację z HRnest odkładamy na końcowy etap projektu
- absencje modelujemy początkowo wewnętrznie lub przez mock
- dokumenty w `docs/context` są źródłem prawdy dla reguł implementacyjnych

## Konsekwencje

Pozytywne:
- łatwiej zachować czyste granice domeny
- można rozwijać model biznesowy bez blokowania się na zewnętrznym API
- integracja z HRnest stanie się adapterem, a nie osią systemu

Koszty:
- część kodu integracyjnego powstanie później
- na początku trzeba utrzymywać wewnętrzny model absencji
- zespół musi pilnować dyscypliny modułowej
