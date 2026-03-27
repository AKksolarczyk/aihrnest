# Workspace module

Aktualny moduł jest podzielony na warstwy:

- `Domain`:
  model domenowy, repozytoria jako interfejsy i serwisy domenowe
- `Application`:
  commandy, query i ich handlery
- `Infrastructure`:
  implementacje repozytoriów plikowych i statyczny layout biura
- `UI`:
  kontrolery HTTP

Zasady:
- kontroler wywoluje command albo query handler
- aplikacja korzysta z interfejsow repozytoriow
- domena nie zna Symfony ani szczegolow zapisu do pliku
