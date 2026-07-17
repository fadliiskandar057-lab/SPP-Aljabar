@forelse($siswa as $item)
    @php
        $monthMap = [
            'januari' => 1, 'jan' => 1, 'january' => 1,
            'februari' => 2, 'feb' => 2, 'february' => 2,
            'maret' => 3, 'mar' => 3, 'march' => 3,
            'april' => 4, 'apr' => 4,
            'mei' => 5, 'may' => 5,
            'juni' => 6, 'jun' => 6, 'june' => 6,
            'juli' => 7, 'jul' => 7, 'july' => 7,
            'agustus' => 8, 'agu' => 8, 'aug' => 8, 'august' => 8,
            'september' => 9, 'sep' => 9,
            'oktober' => 10, 'okt' => 10, 'oct' => 10, 'october' => 10,
            'november' => 11, 'nov' => 11,
            'desember' => 12, 'des' => 12, 'dec' => 12, 'december' => 12,
        ];
        $overdueBills = $item->tagihan
            ->whereNotIn('status', ['lunas', 'gratis'])
            ->filter(fn ($bill) => $bill->jatuh_tempo?->lte(now()))
            ->values();
        $overdueCodes = $overdueBills->map(function ($bill) use ($monthMap) {
            $month = $monthMap[mb_strtolower($bill->bulan)] ?? (is_numeric($bill->bulan) ? (int) $bill->bulan : $bill->bulan);
            return $month.'-'.substr((string) $bill->tahun, -2);
        });
        $arrearsTotal = $item->tagihan->whereNotIn('status', ['lunas', 'gratis'])->sum('nominal');
        $waMessage = "Assalamu'alaikum Bapak/Ibu {$item->nama_orang_tua}, kami dari TU MA Taruna Teknik Al Jabbar menghubungi terkait administrasi SPP ananda {$item->nama}.";
        if ($arrearsTotal > 0) {
            $waMessage .= ' Total tagihan yang perlu ditindaklanjuti sebesar Rp '.number_format($arrearsTotal, 0, ',', '.').'.';
        }
        $waMessage .= ' Terima kasih.';
    @endphp
    <tr class="student-data-row">
        <td>
            <input class="form-check-input student-row-check" type="checkbox" value="{{ $item->id }}" aria-label="Pilih {{ $item->nama }}">
        </td>
        <td><span class="student-nis-pill">{{ $item->nis }}</span></td>
        <td>
            <div class="student-identity">
                <span class="student-mini-avatar">{{ strtoupper(mb_substr($item->nama, 0, 1)) }}</span>
                <div>
                    <strong>{{ $item->nama }}</strong>
                    <small>{{ $item->nama_orang_tua }}</small>
                </div>
            </div>
        </td>
        <td><span class="student-class-chip">{{ $item->kelas->nama_kelas ?? '-' }}</span></td>
        <td><span class="student-muted-text">{{ $item->email ?? '-' }}</span></td>
        <td>
            <div class="student-parent-contact">
                <span>{{ $item->no_hp_orang_tua }}</span>
                <x-whatsapp-link :phone="$item->no_hp_orang_tua" :message="$waMessage" label="Chat" class="btn btn-sm btn-outline-success" />
            </div>
        </td>
        <td><x-status :status="$item->status"/></td>
        <td class="text-end">
            <div class="student-row-actions">
                <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="modal" data-bs-target="#detailSiswa{{ $item->id }}" title="Lihat detail"><i class="bi bi-eye"></i></button>
                <x-whatsapp-link :phone="$item->no_hp_orang_tua" :message="$waMessage" label="" class="btn btn-sm btn-success" title="Chat WhatsApp {{ $item->nama_orang_tua }}" />
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#editSiswa{{ $item->id }}" title="Edit siswa"><i class="bi bi-pencil-square"></i></button>
                <form method="post" action="{{ route('admin.siswa.destroy',$item) }}">
                    @csrf
                    @method('delete')
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus siswa ini?')" title="Hapus siswa"><i class="bi bi-trash"></i></button>
                </form>
            </div>

            <div class="modal fade" id="editSiswa{{ $item->id }}" tabindex="-1" aria-labelledby="editSiswaLabel{{ $item->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content text-start">
                        <form method="post" action="{{ route('admin.siswa.update', $item) }}">
                            @csrf
                            @method('put')
                            <div class="modal-header">
                                <h5 class="modal-title" id="editSiswaLabel{{ $item->id }}">Edit {{ $item->nama }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                @include('admin.siswa_form', ['item' => $item, 'kelas' => $kelas])
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="detailSiswa{{ $item->id }}" tabindex="-1" aria-labelledby="detailSiswaLabel{{ $item->id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content text-start student-detail-modal">
                        <div class="student-detail-hero">
                            <div class="student-avatar">{{ strtoupper(mb_substr($item->nama, 0, 1)) }}</div>
                            <div>
                                <p>Detail siswa</p>
                                <h5 id="detailSiswaLabel{{ $item->id }}">{{ $item->nama }}</h5>
                                <span>{{ $item->nis }} &middot; {{ $item->kelas->nama_kelas ?? '-' }}</span>
                            </div>
                            <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <div class="detail-metrics">
                                <div><span>Status</span><strong>{{ ucfirst($item->status) }}</strong></div>
                                <div><span>Total tagihan</span><strong>{{ $item->tagihan->count() }}</strong></div>
                                <div><span>Tunggakan</span><strong>{{ $overdueBills->count() }}</strong></div>
                            </div>

                            <div class="detail-grid">
                                <div class="detail-card">
                                    <h6>Informasi Siswa</h6>
                                    <dl>
                                        <dt>Email</dt><dd>{{ $item->email ?? '-' }}</dd>
                                        <dt>Kelas</dt><dd>{{ $item->kelas->nama_kelas ?? '-' }}</dd>
                                        <dt>Alamat</dt><dd>{{ $item->alamat ?? '-' }}</dd>
                                    </dl>
                                </div>
                                <div class="detail-card">
                                    <h6>Kontak Orang Tua</h6>
                                    <dl>
                                        <dt>Nama</dt><dd>{{ $item->nama_orang_tua }}</dd>
                                        <dt>No HP</dt>
                                        <dd>
                                            {{ $item->no_hp_orang_tua }}
                                            <div class="mt-2">
                                                <x-whatsapp-link :phone="$item->no_hp_orang_tua" :message="$waMessage" label="Chat Orang Tua" />
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>

                            <div class="detail-card mt-3">
                                <div class="d-flex justify-content-between gap-2 align-items-center mb-2">
                                    <h6 class="mb-0">Data Tunggakan</h6>
                                    <span class="small text-muted">Format bulan-tahun</span>
                                </div>
                                @if($overdueCodes->isNotEmpty())
                                    <div class="overdue-list">
                                        @foreach($overdueCodes as $code)
                                            <span>{{ $code }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="empty-mini">Tidak ada tunggakan sampai hari ini.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr><td colspan="8" class="empty-state">Tidak ada siswa yang cocok dengan pencarian.</td></tr>
@endforelse
