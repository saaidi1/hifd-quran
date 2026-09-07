@extends('layouts.app')

@section('title', __('Teachers and staff'))
@section('page-title', __('Teachers and staff'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0">{{ __('Managing staff and teacher accounts.') }}</p>
        <a href="{{ route('professeurs.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i>{{ __('New account') }}
        </a>
    </div>

    @php
        $roles = ['directeur' => ['danger', __('Director')], 'superviseur' => ['warning', __('Supervisor')],
                  'garde_general' => ['info', __('General guard')], 'professeur' => ['success', __('Teacher')]];
    @endphp

    <div class="app-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Specialty') }}</th>
                        <th>{{ __('Halaqas') }}</th>
                        <th>{{ __('Account') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($professeurs as $p)
                        <tr>
                            <td class="fw-semibold">{{ $p->nom_ar }}</td>
                            <td>
                                @php [$couleur, $lib] = $roles[$p->role->value] ?? ['secondary', $p->role->value]; @endphp
                                <span class="badge text-bg-{{ $couleur }}">{{ $lib }}</span>
                            </td>
                            <td dir="ltr">{{ $p->email }}</td>
                            <td dir="ltr">{{ $p->telephone ?? '—' }}</td>
                            <td class="text-muted">{{ $p->specialite ?? '—' }}</td>
                            <td><span class="badge text-bg-light">{{ $p->groupes_count }}</span></td>
                            <td>
                                <span class="badge text-bg-{{ $p->actif ? 'success' : 'secondary' }}">{{ $p->actif ? __('Enabled') : __('Disabled') }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('professeurs.edit', $p) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('professeurs.toggle', $p) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $p->actif ? 'danger' : 'success' }}"
                                            {{ $p->id === auth()->id() ? 'disabled' : '' }}
                                            onclick="return confirm('{{ __('Confirm account status change?') }}');">
                                        <i class="bi {{ $p->actif ? 'bi-person-x' : 'bi-person-check' }}"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
