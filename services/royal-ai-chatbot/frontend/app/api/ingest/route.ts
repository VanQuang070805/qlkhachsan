import { NextRequest, NextResponse } from 'next/server';

export async function POST(_request: NextRequest) {
  return NextResponse.json(
    {
      error:
        'PDF upload is disabled in Royal Hotel. Approved hotel knowledge is synchronized via server-side CLI (`yarn --cwd backend sync:knowledge` or `php artisan knowledge:index`).',
    },
    { status: 403 },
  );
}
