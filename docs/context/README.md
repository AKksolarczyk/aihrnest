# Context aplikacji

Ten katalog jest źródłem prawdy dla reguł tworzenia i rozwijania kodu aplikacji.

Jeżeli implementacja, pomysł lub nowa decyzja są sprzeczne z dokumentami w `docs/context`, należy:
- najpierw zaktualizować dokumentację i decyzje
- dopiero później zmieniać kod

Kolejność czytania:
- [product.md](/Users/jaroslawkasica/srv/aiCoding/docs/context/product.md)
- [ubiquitous-language.md](/Users/jaroslawkasica/srv/aiCoding/docs/context/ubiquitous-language.md)
- [business-rules.md](/Users/jaroslawkasica/srv/aiCoding/docs/context/business-rules.md)
- [invariants.md](/Users/jaroslawkasica/srv/aiCoding/docs/context/invariants.md)
- [architecture.md](/Users/jaroslawkasica/srv/aiCoding/docs/context/architecture.md)
- [bounded-contexts.md](/Users/jaroslawkasica/srv/aiCoding/docs/context/bounded-contexts.md)
- [coding-rules.md](/Users/jaroslawkasica/srv/aiCoding/docs/context/coding-rules.md)
- [decisions/0001-initial-rules.md](/Users/jaroslawkasica/srv/aiCoding/docs/context/decisions/0001-initial-rules.md)

Zasady obowiązujące od początku projektu:
- aplikacja jest budowana w PHP i Symfony
- architektura domenowa ma być zgodna z DDD
- komunikacja wewnątrz aplikacji ma respektować CQRS
- kod ma być zgodny z dobrymi praktykami PHP i Symfony
- obecnie akceptujemy atrybuty Doctrine bezpośrednio w modelach domenowych
- integracja z HRnest nie jest realizowana teraz, wracamy do niej na końcu
- do czasu integracji z HRnest używamy wewnętrznego modelu absencji lub mocka

Reguły pracy z dokumentacją:
- decyzje architektoniczne zapisujemy jako ADR w `docs/context/decisions`
- nowe bounded contexty dopisujemy do `bounded-contexts.md`
- nowe wyjątki od reguł muszą mieć osobną decyzję ADR
