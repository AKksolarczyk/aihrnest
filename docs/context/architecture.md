# Architektura

## Zasady nadrzędne

- Symfony jest frameworkiem dostarczającym runtime, HTTP, DI i integracje techniczne
- domena nie może zależeć od Symfony ani infrastruktury
- logika biznesowa ma być modelowana w duchu DDD
- od początku rozdzielamy operacje zapisujące od odczytowych zgodnie z CQRS
- kod ma być projektowany modułowo, bez centralnego `Service` z całą logiką

## Warstwy

### Domain

Warstwa domenowa zawiera:
- encje
- value objecty
- agregaty
- domenowe serwisy
- polityki biznesowe
- zdarzenia domenowe
- repozytoria jako interfejsy

Warstwa domenowa:
- nie zna Doctrine
- nie zna Symfony
- nie zna HTTP
- nie używa tablic jako kontraktów biznesowych, jeśli można użyć jawnych typów

### Application

Warstwa aplikacyjna zawiera:
- commandy
- query
- handlery commandów
- handlery query
- porty do infrastruktury zewnętrznej
- orkiestrację use case'ów

Warstwa aplikacyjna:
- nie zawiera logiki frameworkowej
- nie zawiera SQL ani mapowania ORM
- deleguje reguły biznesowe do domeny

### Infrastructure

Warstwa infrastrukturalna zawiera:
- implementacje repozytoriów
- mapowanie Doctrine
- kontrolery HTTP
- integracje z zewnętrznymi systemami
- transporty wiadomości
- cache, logowanie i konfigurację techniczną

### UI

Warstwa UI:
- renderuje widoki lub wystawia API
- nie zawiera reguł biznesowych
- mapuje request na command lub query
- mapuje wynik use case'a na response

## Docelowy układ katalogów

Kod aplikacyjny powinien ewoluować w stronę:

```text
src/
  DeskBooking/
    Domain/
    Application/
    Infrastructure/
    UI/
  RoomBooking/
    Domain/
    Application/
    Infrastructure/
    UI/
  Absence/
    Domain/
    Application/
    Infrastructure/
    UI/
  Shared/
    Domain/
    Application/
    Infrastructure/
```

## CQRS

Reguły CQRS dla projektu:
- command zmienia stan i niczego nie renderuje
- query nie zmienia stanu
- modele odczytowe mogą być prostsze niż model domenowy
- read model może być zbudowany osobno od agregatów
- nie mieszamy handlerów command i query w jednej klasie

## Integracje zewnętrzne

Integracje zewnętrzne mają być budowane przez porty i adaptery:
- port definiujemy po stronie aplikacji
- adapter implementujemy po stronie infrastruktury
- domena nie zna zewnętrznego API

HRnest:
- teraz nie implementujemy
- kontrakty pod integrację można przygotować dopiero wtedy, gdy będą potrzebne
- nie należy uzależniać modelu domenowego od szczegółów API HRnest
