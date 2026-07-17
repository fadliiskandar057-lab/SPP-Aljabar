<input name="nis" class="form-control mb-2" placeholder="NIS" value="{{ old('nis',$item->nis ?? '') }}" required>
<input name="nama" class="form-control mb-2" placeholder="Nama siswa" value="{{ old('nama',$item->nama ?? '') }}" required>
<input name="email" type="email" class="form-control mb-2" placeholder="Email siswa" value="{{ old('email',$item->email ?? '') }}">
<select name="kelas_id" class="form-select mb-2" required><option value="">Pilih kelas</option>@foreach($kelas as $k)<option value="{{ $k->id }}" @selected((string) old('kelas_id', $item->kelas_id ?? '') === (string) $k->id)>{{ $k->nama_kelas }}</option>@endforeach</select>
<input name="nama_orang_tua" class="form-control mb-2" placeholder="Nama orang tua" value="{{ old('nama_orang_tua',$item->nama_orang_tua ?? '') }}" required>
<input name="no_hp_orang_tua" class="form-control mb-2" placeholder="No HP 628..." value="{{ old('no_hp_orang_tua',$item->no_hp_orang_tua ?? '') }}" required>
<textarea name="alamat" class="form-control mb-2" placeholder="Alamat">{{ old('alamat',$item->alamat ?? '') }}</textarea>
<select name="status" class="form-select mb-3"><option value="aktif" @selected(old('status', $item->status ?? 'aktif') === 'aktif')>Aktif</option><option value="lulus" @selected(old('status', $item->status ?? '') === 'lulus')>Lulus</option><option value="keluar" @selected(old('status', $item->status ?? '') === 'keluar')>Keluar</option></select>
