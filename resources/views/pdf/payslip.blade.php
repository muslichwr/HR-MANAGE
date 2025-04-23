<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Slip Gaji - {{ $payslip->employee->full_name }}</title>
    <style>
        @page {
            margin: 0.5cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 20px;
        }
        .header {
            padding: 10px 0;
            border-bottom: 2px solid #333;
            margin-bottom: 20px;
        }
        .logo {
            max-height: 60px;
            max-width: 200px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        .company-details {
            font-size: 11px;
        }
        .payslip-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            padding: 5px 0;
            background-color: #f0f0f0;
            border-radius: 5px;
        }
        .employee-details, .payslip-details {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table.employee-table td {
            padding: 4px 10px;
        }
        table.component-table {
            margin-bottom: 20px;
        }
        table.component-table th {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        table.component-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .component-total {
            font-weight: bold;
            text-align: right;
            padding: 8px;
            background-color: #f9f9f9;
        }
        .net-salary {
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
            border-top: 2px solid #333;
            padding-top: 10px;
            text-align: right;
        }
        .signature-area {
            margin-top: 50px;
            text-align: right;
        }
        .signature-img {
            height: 60px;
            margin-bottom: 10px;
        }
        .signature-name {
            font-weight: bold;
            border-top: 1px solid #333;
            display: inline-block;
            min-width: 200px;
            text-align: center;
            padding-top: 5px;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            text-align: center;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .currency {
            text-align: right;
        }
        .periode {
            margin-bottom: 15px;
        }
        .earnings {
            background-color: #f5faff;
        }
        .deductions {
            background-color: #fff5f5;
        }
        .text-danger {
            color: #dc3545;
        }
        .copyright {
            font-size: 9px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header/Kop Surat -->
        <div class="header">
            <table width="100%">
                <tr>
                    <td width="20%">
                        @if(file_exists($logoUrl))
                            <img src="{{ $logoUrl }}" alt="Logo Perusahaan" class="logo">
                        @else
                            <div style="height: 60px; width: 200px; border: 1px dashed #ccc; text-align: center; line-height: 60px;">Logo</div>
                        @endif
                    </td>
                    <td width="80%" style="vertical-align: top; padding-left: 20px;">
                        <h1 class="company-name">PT. HR MANAGE INDONESIA</h1>
                        <div class="company-details">
                            Jl. Sudirman No. 123, Jakarta Pusat<br>
                            Telepon: (021) 1234-5678 | Email: hrd@hrmanage.id<br>
                            www.hrmanage.id
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Judul Slip Gaji -->
        <div class="payslip-title">SLIP GAJI KARYAWAN</div>
        
        <!-- Periode Gaji -->
        <div class="periode">
            <strong>Periode:</strong> {{ $bulanText }} {{ $payslip->year }}
        </div>
        
        <!-- Detail Karyawan -->
        <div class="employee-details">
            <table class="employee-table" width="100%">
                <tr>
                    <td width="20%"><strong>NIK</strong></td>
                    <td width="30%">: {{ $payslip->employee->nik }}</td>
                    <td width="20%"><strong>Departemen</strong></td>
                    <td width="30%">: {{ $payslip->employee->department->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Nama</strong></td>
                    <td>: {{ $payslip->employee->full_name }}</td>
                    <td><strong>Jabatan</strong></td>
                    <td>: {{ $payslip->employee->position->title ?? '-' }}</td>
                </tr>
            </table>
        </div>
        
        <!-- Komponen Pendapatan -->
        <div class="payslip-components">
            <table class="component-table earnings">
                <tr>
                    <th colspan="2">PENDAPATAN</th>
                </tr>
                @php $totalEarnings = 0; @endphp
                
                @forelse($earningComponents as $component)
                    <tr>
                        <td>{{ $component->component->name }}</td>
                        <td class="currency">Rp {{ number_format($component->amount, 0, ',', '.') }}</td>
                    </tr>
                    @php $totalEarnings += $component->amount; @endphp
                @empty
                    <tr>
                        <td colspan="2" style="text-align: center;">Tidak ada komponen pendapatan</td>
                    </tr>
                @endforelse
                
                <tr>
                    <td class="component-total">Total Pendapatan</td>
                    <td class="component-total currency">Rp {{ number_format($totalEarnings, 0, ',', '.') }}</td>
                </tr>
            </table>
            
            <!-- Komponen Potongan -->
            <table class="component-table deductions">
                <tr>
                    <th colspan="2">POTONGAN</th>
                </tr>
                @php $totalDeductions = 0; @endphp
                
                @forelse($deductionComponents as $component)
                    <tr>
                        <td>{{ $component->component->name }}</td>
                        <td class="currency text-danger">Rp {{ number_format($component->amount, 0, ',', '.') }}</td>
                    </tr>
                    @php $totalDeductions += $component->amount; @endphp
                @empty
                    <tr>
                        <td colspan="2" style="text-align: center;">Tidak ada komponen potongan</td>
                    </tr>
                @endforelse
                
                <tr>
                    <td class="component-total">Total Potongan</td>
                    <td class="component-total currency text-danger">Rp {{ number_format($totalDeductions, 0, ',', '.') }}</td>
                </tr>
            </table>
            
            <!-- Gaji Bersih -->
            <div class="net-salary">
                <table width="100%">
                    <tr>
                        <td width="70%" style="text-align: right;"><strong>GAJI BERSIH:</strong></td>
                        <td width="30%" style="text-align: right;"><strong>Rp {{ number_format($payslip->net_salary, 0, ',', '.') }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Area Tanda Tangan -->
        <div class="signature-area">
            <p>Jakarta, {{ $tanggalCetak }}</p>
            @if(file_exists($signature))
                <img src="{{ $signature }}" alt="Tanda Tangan Direktur" class="signature-img">
            @else
                <div style="height: 60px; width: 200px; margin-left: auto;"></div>
            @endif
            <p class="signature-name">Direktur HR</p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Slip Gaji ini diterbitkan secara elektronik dan tidak memerlukan tanda tangan basah.</p>
            <p>Jika ada pertanyaan mengenai isi slip gaji ini, harap menghubungi bagian HRD.</p>
            <p class="copyright">© {{ date('Y') }} PT. HR Manage Indonesia. All rights reserved.</p>
        </div>
    </div>
</body>
</html> 