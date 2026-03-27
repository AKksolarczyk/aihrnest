# Bounded contexts

## Zasada podziału

Dzielimy system według odpowiedzialności biznesowej, a nie według typu technicznego.

## Początkowe bounded contexty

### DeskBooking

Odpowiedzialność:
- rezerwacje biurek
- reguły dostępności biurek
- przypisania i zwalnianie biurek

Przykładowe pojęcia:
- Desk
- DeskAssignment
- DeskReservation
- Availability

### RoomBooking

Odpowiedzialność:
- rezerwacje sal konferencyjnych
- reguły konfliktów czasowych
- kalendarz sal

Przykładowe pojęcia:
- Room
- RoomReservation
- ReservationWindow

### OfficeLayout

Odpowiedzialność:
- budynki, piętra, pomieszczenia
- pozycjonowanie zasobów na mapie
- konfiguracja interaktywnej mapy

Przykładowe pojęcia:
- Office
- Floor
- RoomArea
- DeskPosition

### Absence

Odpowiedzialność:
- absencje użytkowników
- wpływ absencji na dostępność zasobów
- polityki zwalniania rezerwacji

Przykładowe pojęcia:
- Absence
- AbsenceType
- AbsencePeriod

### User

Odpowiedzialność:
- konto użytkownika
- podstawowe dane użytkownika
- role i uprawnienia

Przykładowe pojęcia:
- User
- UserId
- Role

## Reguły między contextami

- bounded context komunikuje się z innym przez jawne kontrakty
- nie współdzielimy encji pomiędzy contextami
- współdzielone typy przenosimy do `Shared`, tylko jeśli faktycznie są wspólne
- zależności mają być skierowane do środka modelu, nie między infrastrukturami modułów
