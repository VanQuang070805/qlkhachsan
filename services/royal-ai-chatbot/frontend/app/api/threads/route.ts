import { NextResponse } from 'next/server';
import { createServerClient } from '@/lib/langgraph-server';

export async function POST() {
  try {
    const serverClient = createServerClient();
    const thread = await serverClient.createThread({
      surface: 'nextjs-inspector',
    });
    return NextResponse.json({ thread_id: thread.thread_id });
  } catch (error) {
    console.error('Error creating thread:', error);
    return NextResponse.json(
      { error: 'Failed to create thread' },
      { status: 500 },
    );
  }
}
