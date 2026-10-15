<input name="nis" class="form-control mb-2" placeholder="NIS" value="{{ old('nis',$item->nis ?? '') }}" required>
<input name="nama" class="form-control mb-2" placeholder="Nama siswa" value="{{ old('nama',$item->nama ?? '') }}" required>
<input name="email" type="email" class="form-control mb-2" placeholder="Email siswa" value="{{ old('email',$item->email ?? '') }}">
<select name="kelas_id" class="form-select mb-2" required><option value="">Pilih kelas</option>@foreach($kelas as $k)<option value="{{ $k->id }}" @selected((string) old('kelas_id', $item->kelas_id ?? '') === (string) $k->id)>{{ $k->nama_kelas }}</option>@endforeach</select>
<label class="form-label">Angkatan masuk (tetap saat naik kelas)</label>
<input name="angkatan" type="number" min="1901" max="2155" class="form-control mb-2" placeholder="Contoh: 2026" value="{{ old('angkatan', $item->angkatan ?? '') }}">
<label class="form-label">Tahun ajaran kelas aktif</label>
<select name="tahun_ajaran_id" class="form-select mb-2"><option value="">Belum ditentukan</option>@foreach($tahunList ?? collect() as $year)<option value="{{ $year->id }}" @selected((string) old('tahun_ajaran_id', $item->tahun_ajaran_id ?? '') === (string) $year->id)>{{ $year->nama }}</option>@endforeach</select>
<input name="nama_orang_tua" class="form-control mb-2" placeholder="Nama orang tua" value="{{ old('nama_orang_tua',$item->nama_orang_tua ?? '') }}" required>
<input name="no_hp_orang_tua" class="form-control mb-2" placeholder="No HP 628..." value="{{ old('no_hp_orang_tua',$item->no_hp_orang_tua ?? '') }}" required>
<textarea name="alamat" class="form-control mb-2" placeholder="Alamat">{{ old('alamat',$item->alamat ?? '') }}</textarea>
<select name="status" class="form-select mb-3"><option value="aktif" @selected(old('status', $item->status ?? 'aktif') === 'aktif')>Aktif</option><option value="lulus" @selected(old('status', $item->status ?? '') === 'lulus')>Lulus</option><option value="keluar" @selected(old('status', $item->status ?? '') === 'keluar')>Keluar</option></select>
