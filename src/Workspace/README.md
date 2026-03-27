# Workspace module

Aktualny moduł jest podzielony na warstwy:

- `Domain`:
  model domenowy, repozytoria jako interfejsy i serwisy domenowe
- `Application`:
  commandy, query i ich handlery
- `Infrastructure`:
  implementacje repozytoriów Doctrine i statyczny layout biura
- `UI`:
  kontrolery HTTP

Zasady:
- kontroler wywoluje command albo query handler
- aplikacja korzysta z interfejsow repozytoriow
- domena akceptuje atrybuty Doctrine w modelach
- logika biznesowa nie trafia do repozytoriow ani kontrolerow
