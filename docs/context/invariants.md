# Inwarianty

Ten dokument opisuje warunki, ktore system musi chronic w modelu domenowym i use case'ach.

## User

- `id` uzytkownika nie moze byc puste.
- `email` uzytkownika nie moze byc puste.
- `email` uzytkownika jest unikalny.
- `passwordHash` nie moze byc pusty.
- `assignedDeskId`, jesli wystepuje, musi wskazywac istniejace biurko.
- `vacationDaysTotal` nie moze byc ujemne.
- `vacationDaysRemaining` nie moze byc ujemne.
- `vacationDaysRemaining` nie moze zejsc ponizej zera w wyniku operacji biznesowej.
- Konto nieaktywne nie moze przejsc procesu logowania.
- Aktywacja konta usuwa token potwierdzajacy.

## Vacation

- `id` urlopu nie moze byc puste.
- `userId` urlopu nie moze byc puste.
- `endDate` nie moze byc wczesniejsze niz `startDate`.
- Zakres nowego urlopu nie moze nachodzic na istniejacy urlop tego samego uzytkownika.
- Zlozenie urlopu i odjecie dni urlopowych musza byc spojna jedna operacja biznesowa.

## DeskClaim

- `id` rezerwacji biurka nie moze byc puste.
- `userId` rezerwacji biurka nie moze byc puste.
- `deskId` rezerwacji biurka nie moze byc puste.
- Nie mozna zapisac zajecia nieistniejacego biurka.
- Nie mozna skutecznie zajac biurka, ktore jest juz zajete na ten sam dzien.

## Use case'y

- Command zapisujacy nie moze zostawic systemu w stanie czesciowo zmienionym.
- Walidacja biznesowa musi nastapic przed trwa zapisaniem zmian.
- Read model moze upraszczac dane, ale nie moze obchodzic inwariantow modelu zapisu.

## Zakres odpowiedzialnosci

- Inwarianty lokalne do encji powinny byc chronione przez konstruktor lub metody domenowe.
- Inwarianty przecinajace wiele rekordow, na przyklad zakaz nachodzenia urlopow, sa chronione przez use case i repozytorium.
