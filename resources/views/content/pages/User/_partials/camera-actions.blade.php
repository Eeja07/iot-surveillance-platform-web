<div class="d-flex align-items-center">
    <a href="{{ route('user.my-cameras.edit', $camera->id) }}" class="btn btn-sm btn-icon item-edit"><i
            class="ti ti-edit"></i></a>
    <form action="{{ route('user.my-cameras.destroy', $camera->id) }}" method="POST"
        onsubmit="return confirm('Anda yakin ingin melepaskan kamera ini dari akun Anda?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-icon item-delete"><i class="ti ti-trash"></i></button>
    </form>
</div>
