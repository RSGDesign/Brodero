# 🚀 GHID RAPID - Sistem Descărcări Fișiere

## ⚡ Start Rapid (5 Minute Setup)

### 1️⃣ Rulează SQL (1 minut)
```bash
# În phpMyAdmin sau terminal MySQL
mysql -u root -p brodero < database_update_downloads.sql
```

### 2️⃣ Sincronizează Comenzi Vechi (30 secunde)
```
👉 Accesează: https://brodero.online/admin/sync_downloads.php
👉 Click: "Sincronizează Acum"
👉 Verifică: "X comenzi actualizate"
```

### 3️⃣ Testează Plată Stripe (2 minute)
```
1. Deschide: https://brodero.online/pages/magazin.php
2. Adaugă produs digital în coș
3. Checkout → Stripe
4. Card test: 4242 4242 4242 4242
5. Verifică: "Fișiere Descărcabile" → Buton verde ✅
```

### 4️⃣ Verifică Status (30 secunde)
```
👉 Accesează: https://brodero.online/admin/test_downloads.php
👉 Verifică: "total_problematic_orders": 0
```

---

## 📞 Comenzi Rapide

### Verificare Comandă Specifică
```bash
https://brodero.online/admin/test_downloads.php?order_id=123
```

### Sincronizare Manuală (SQL)
```sql
UPDATE order_items oi
JOIN orders o ON o.id = oi.order_id
SET oi.downloads_enabled = 1
WHERE o.payment_status = 'paid';
```

### Activare Descărcări pentru Comandă X
```sql
UPDATE order_items SET downloads_enabled = 1 WHERE order_id = 123;
```

---

## 🎯 Workflow-uri Principale

### Client Plătește cu Stripe
```
Stripe → Redirecționare automată → Descărcări ACTIVE ✅
```

### Client Plătește prin Transfer
```
Transfer → Admin Confirmă → Descărcări ACTIVE ✅
```

### Admin Confirmă Plată
```
Admin Panel → Status = "Plătit" → SAVE → Descărcări ACTIVE ✅
```

---

## ⚠️ Troubleshooting Ultra-Rapid

### Problema: "Plată OK, dar descărcări blocate"
**Soluție 1-Click:**
```
https://brodero.online/admin/sync_downloads.php → Click "Sincronizează"
```

### Problema: "Toate comenzile vechi sunt blocate"
**Soluție 1-Click:**
```
https://brodero.online/admin/sync_downloads.php → Click "Sincronizează"
```

### Problema: "Client specific nu poate descărca"
**Soluție SQL:**
```sql
-- Găsește comanda clientului
SELECT o.id, o.order_number FROM orders o 
JOIN users u ON u.id = o.user_id 
WHERE u.email = 'client@email.com';

-- Activează descărcări pentru acea comandă
UPDATE order_items SET downloads_enabled = 1 WHERE order_id = [ID_COMANDA];
```

---

## 📊 Statusuri în "Fișiere Descărcabile"

| Status | Ce înseamnă | Acțiune |
|--------|-------------|---------|
| 🟢 **Disponibil** | Poți descărca | Click "Descarcă" |
| 🟡 **Plată în așteptare** | Transfer neconfirmat | Așteaptă confirmare admin |
| 🔴 **În procesare** | Bug - plată OK, descărcări NU | Rulează sync sau contactează admin |
| ⚫ **Limită atinsă** | Ai depășit nr. descărcări | Contactează suportul |

---

## 🔗 Link-uri Utile

| Funcție | URL |
|---------|-----|
| **Sincronizare** | `/admin/sync_downloads.php` |
| **Debug** | `/admin/test_downloads.php?order_id=X` |
| **Comenzi** | `/admin/admin_orders.php` |
| **Fișiere Client** | `/pages/cont.php?tab=fisiere` |

---

## ✅ Checklist Rapid după Implementare

- [ ] SQL rulat
- [ ] Sync efectuat (0 comenzi problematice)
- [ ] Test plată Stripe → descărcări disponibile
- [ ] Test confirmare transfer → descărcări disponibile
- [ ] Toate comenzile vechi au descărcări active

---

## 💡 Tips & Tricks

### Verificare Rapidă Toate Comenzile
```sql
SELECT 
    o.order_number,
    o.payment_status,
    COUNT(oi.id) as items,
    SUM(oi.downloads_enabled) as enabled
FROM orders o
JOIN order_items oi ON oi.order_id = o.id
WHERE o.payment_status = 'paid'
GROUP BY o.id
HAVING enabled < items;
```

### Activare în Bulk pentru Toate Comenzile Plătite
```sql
UPDATE order_items oi
JOIN orders o ON o.id = oi.order_id
SET oi.downloads_enabled = 1
WHERE o.payment_status = 'paid';
```

### Dezactivare Descărcări (Rollback)
```sql
UPDATE order_items SET downloads_enabled = 0 WHERE order_id = 123;
```

---

## 📞 Suport Rapid

**Întrebări frecvente:**
- **Q:** Pot rula sync de mai multe ori?  
  **A:** Da, este safe. Va actualiza doar comenzile care trebuie.

- **Q:** Ce se întâmplă cu comenzile viitoare?  
  **A:** Se activează automat. Nu mai e nevoie de sync.

- **Q:** Cât durează sincronizarea?  
  **A:** ~1 secundă per 100 comenzi.

---

**🎯 Remember:** Odată implementat, sistemul funcționează 100% automat. Sync-ul e necesar doar pentru comenzile vechi!
