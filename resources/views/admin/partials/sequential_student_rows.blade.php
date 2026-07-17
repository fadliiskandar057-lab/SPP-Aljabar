@forelse($students as $item)
    <tr>
        <td>{{ $item->nis }}</td>
        <td>{{ $item->nama }}</td>
        <td>{{ $item->kelas->nama_kelas ?? '-' }}</td>
        <td><x-status :status="$item->status"/></td>
    </tr>
@empty
    <tr><td colspan="4" class="empty-state">Tidak ada siswa yang cocok.</td></tr>
@endforelse
