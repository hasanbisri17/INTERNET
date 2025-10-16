# Template Invoice Modern

## 🎨 Overview
Template invoice baru dengan desain modern dan profesional, terinspirasi dari desain contemporary invoice template.

## ✨ Design Features

### 1. **Header Section**
- Logo perusahaan di kiri (dengan icon biru)
- Tulisan "Invoice" besar di kanan (warna biru #5B6FFF)
- Layout 2 kolom yang seimbang

### 2. **Invoice Info Layout**
**Kolom Kiri - "Invoice to:"**
- Nama customer (besar, bold, biru)
- Email
- Phone number

**Kolom Kanan - "Invoice detail:"**
- Number: Invoice number
- Due date: Tanggal jatuh tempo
- Status badge (pending/paid/overdue)

### 3. **Items Table**
- Header biru (#5B6FFF) dengan text putih
- Kolom: NO, PRODUCT, QTY, PRICE, TOTAL
- Clean spacing & borders
- Center alignment untuk nomor & angka

### 4. **Summary Section**
- Float di kanan
- Total row
- Discount row (jika ada)
- GRAND TOTAL dengan background biru

### 5. **Payment Info Section**
- Icons dengan background biru circular
- Phone dengan icon telepon
- Email dengan icon email
- Address dengan icon location

### 6. **Decorative Elements**
- Circle biru di pojok kanan atas
- Circle gradient di pojok kiri bawah
- Modern, clean, professional

## 🎨 Color Palette

```css
Primary Blue: #5B6FFF
Light Blue: #8B9FFF
Background: #FFFFFF
Text: #333333
Light Text: #666666
Borders: #E5E7EB
Background Light: #F9FAFB

Status Colors:
- Pending: #FEF3C7 / #92400E
- Paid: #D1FAE5 / #065F46
- Overdue: #FEE2E2 / #991B1B
```

## 📐 Typography

```css
Font Family: Arial, Helvetica, sans-serif
- Invoice Title: 64px, bold
- Company Name: 28px, bold
- Customer Name: 24px, bold
- Section Title: 18-20px, bold
- Body Text: 14px
- Table Header: 14px, uppercase
```

## 🎯 Sections

### 1. Header
```html
Logo (60x60) + Company Name | Invoice (64px)
```

### 2. Info Section
```
Invoice to:              Invoice detail:
[Customer Name]          Number: XXX
email                    Due date: XX/XX/XX
phone                    [Status Badge]
```

### 3. Table
```
NO | PRODUCT | QTY | PRICE | TOTAL
1  | Internet Package | 1 | Rp XXX | Rp XXX
```

### 4. Summary
```
Total          Rp XXX,XXX
Discount       Rp XXX
GRAND TOTAL    Rp XXX,XXX (blue bg)
```

### 5. Payment Info
```
🔵 📞 +62-XXX-XXX-XXXX
🔵 ✉️  email@company.com
🔵 📍 Address
```

## 📊 Layout Structure

```
┌─────────────────────────────────────┐
│ [Logo] Company Name      Invoice    │ (Header)
├─────────────────────────────────────┤
│ Invoice to:    │  Invoice detail:   │ (Info)
│ Customer       │  Number: XXX       │
│ email          │  Due: XX/XX/XX     │
│ phone          │  [Status]          │
├─────────────────────────────────────┤
│ NO│PRODUCT│QTY│PRICE│TOTAL          │ (Table)
│ 1 │  ...  │ 1 │ XXX │ XXX           │
├─────────────────────────────────────┤
│                      Total: Rp XXX  │ (Summary)
│                   Discount: Rp XX   │
│              GRAND TOTAL: Rp XXX    │
├─────────────────────────────────────┤
│ Payment Info:                       │ (Payment)
│ 🔵 Phone                            │
│ 🔵 Email                            │
│ 🔵 Address                          │
└─────────────────────────────────────┘
```

## 🔧 Configuration

Template ini menggunakan settings dari database:

### Company Info (dari Settings):
```php
config('app.name')                    // Company name
Setting::get('company_phone')         // Phone number
Setting::get('company_email')         // Email
Setting::get('company_address')       // Address
Setting::get('invoice_notes')         // Optional notes
```

### Customer Info (dari Payment):
```php
$payment->customer->name              // Customer name
$payment->customer->email             // Customer email
$payment->customer->phone             // Customer phone
$payment->invoice_number              // Invoice number
$payment->due_date                    // Due date
$payment->status                      // Status (pending/paid/overdue)
$payment->internetPackage->name       // Product name
$payment->amount                      // Amount
```

## 🎨 Responsive Design

Template dirancang untuk A4 PDF:
- Width: 100%
- Padding: 40px
- Font-size yang optimal untuk print
- Tidak menggunakan media queries (PDF static)

## 📱 Icons

Menggunakan inline SVG untuk icons:
- Phone icon
- Email icon
- Location icon
- Grid/chart icon untuk logo

## 🔍 Details

### Status Badges
```php
@php
    $statusClass = 'status-pending';
    if ($payment->status === 'paid') {
        $statusClass = 'status-paid';
    } elseif ($payment->status === 'overdue') {
        $statusClass = 'status-overdue';
    }
@endphp
```

### Table Structure
- 5 columns: NO, PRODUCT, QTY, PRICE, TOTAL
- Header: Blue background, white text
- Body: Clean, bordered rows
- Numbers & qty: center aligned

### Summary Calculation
```php
Total: $payment->amount
Discount: $payment->discount ?? 0
Grand Total: $payment->amount - ($payment->discount ?? 0)
```

## 🎯 Usage

Template otomatis digunakan saat:
1. Generate tagihan bulanan
2. Kirim invoice via WhatsApp
3. Download invoice dari admin panel

## 📂 Files Modified

1. `resources/views/invoice-modern.blade.php` ← New template
2. `app/Services/WhatsAppService.php` → Use 'invoice-modern'
3. `app/Http/Controllers/InvoiceController.php` → Use 'invoice-modern'

## ✅ Features Checklist

- [x] Modern header with logo & title
- [x] Two-column info layout
- [x] Professional table design
- [x] Blue color scheme (#5B6FFF)
- [x] Status badges (pending/paid/overdue)
- [x] Payment info with icons
- [x] Decorative circles
- [x] Summary section with grand total
- [x] Responsive to settings
- [x] PDF-optimized styling
- [x] Clean typography

## 🚀 Benefits

1. **Professional:** Modern, clean design
2. **Branded:** Consistent color scheme
3. **Readable:** Clear hierarchy & spacing
4. **Informative:** All essential info visible
5. **Printable:** Optimized for A4 paper
6. **Flexible:** Uses dynamic settings

---

**Created:** 13 Oktober 2025  
**Version:** 1.0  
**Template:** invoice-modern.blade.php  
**Status:** ✅ Production Ready

