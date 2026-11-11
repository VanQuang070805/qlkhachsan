'use client';

import type React from 'react';
import { useToast } from '@/hooks/use-toast';
import { useRef, useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ArrowUp, Loader2 } from 'lucide-react';
import { ExamplePrompts } from '@/components/example-prompts';
import { ChatMessage } from '@/components/chat-message';
import SplitText from '@/components/reactbits/SplitText';
import BlurText from '@/components/reactbits/BlurText';
import Magnet from '@/components/reactbits/Magnet';
import {
  PDFDocument,
  RetrieveDocumentsNodeUpdates,
} from '@/types/graphTypes';

export default function Home() {
  const { toast } = useToast();
  const [messages, setMessages] = useState<
    Array<{
      role: 'user' | 'assistant';
      content: string;
      sources?: PDFDocument[];
    }>
  >([]);
  const [input, setInput] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [threadId, setThreadId] = useState<string | null>(null);
  const abortControllerRef = useRef<AbortController | null>(null);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const lastRetrievedDocsRef = useRef<PDFDocument[]>([]);

  useEffect(() => {
    const initThread = async () => {
      if (threadId) return;
      try {
        const res = await fetch('/api/threads', { method: 'POST' });
        if (!res.ok) throw new Error('Failed to initialize thread');
        const data = await res.json();
        setThreadId(data.thread_id);
      } catch (error) {
        console.error('Error creating thread:', error);
        toast({
          title: 'Error',
          description:
            'Could not connect to Royal Hotel LangGraph server via /api/threads.',
          variant: 'destructive',
        });
      }
    };
    initThread();
  }, [threadId, toast]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!input.trim() || !threadId || isLoading) return;

    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }

    const userMessage = input.trim();
    setMessages((prev) => [
      ...prev,
      { role: 'user', content: userMessage, sources: undefined },
      { role: 'assistant', content: '', sources: undefined },
    ]);
    setInput('');
    setIsLoading(true);

    const abortController = new AbortController();
    abortControllerRef.current = abortController;
    lastRetrievedDocsRef.current = [];

    try {
      const response = await fetch('/api/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          message: userMessage,
          threadId,
        }),
        signal: abortController.signal,
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const reader = response.body?.getReader();
      if (!reader) throw new Error('No reader available');

      const decoder = new TextDecoder();

      while (true) {
        const { done, value } = await reader.read();
        if (done) break;

        const chunkStr = decoder.decode(value);
        const lines = chunkStr.split('\n').filter(Boolean);

        for (const line of lines) {
          if (!line.startsWith('data: ')) continue;

          const sseString = line.slice('data: '.length);
          let sseEvent: any;
          try {
            sseEvent = JSON.parse(sseString);
          } catch {
            continue;
          }

          const { event, data } = sseEvent;

          if (event === 'messages/partial') {
            if (Array.isArray(data)) {
              const lastObj = data[data.length - 1];
              if (lastObj?.type === 'ai') {
                const partialContent = lastObj.content ?? '';
                if (
                  typeof partialContent === 'string' &&
                  !partialContent.startsWith('{')
                ) {
                  setMessages((prev) => {
                    const newArr = [...prev];
                    if (
                      newArr.length > 0 &&
                      newArr[newArr.length - 1].role === 'assistant'
                    ) {
                      newArr[newArr.length - 1].content = partialContent;
                      newArr[newArr.length - 1].sources =
                        lastRetrievedDocsRef.current;
                    }
                    return newArr;
                  });
                }
              }
            }
          } else if (event === 'updates' && data) {
            if (
              data &&
              typeof data === 'object' &&
              'retrieveDocuments' in data &&
              data.retrieveDocuments &&
              Array.isArray(data.retrieveDocuments.documents)
            ) {
              const retrievedDocs = (data as RetrieveDocumentsNodeUpdates)
                .retrieveDocuments.documents as PDFDocument[];
              lastRetrievedDocsRef.current = retrievedDocs;
            }
          }
        }
      }
    } catch (error) {
      toast({
        title: 'Error',
        description:
          'Failed to send message: ' +
          (error instanceof Error ? error.message : 'Unknown error'),
        variant: 'destructive',
      });
      setMessages((prev) => {
        const newArr = [...prev];
        newArr[newArr.length - 1].content =
          'Xin lỗi, đã xảy ra lỗi khi xử lý câu hỏi.';
        return newArr;
      });
    } finally {
      setIsLoading(false);
      abortControllerRef.current = null;
    }
  };

  return (
    <main className="flex min-h-screen flex-col items-center p-4 md:p-24 max-w-5xl mx-auto w-full">
      {messages.length === 0 ? (
        <>
          <div className="flex-1 flex items-center justify-center">
            <div className="text-center space-y-3">
              <SplitText
                text="Rosaliza Hotel · LangGraph + Gemini Pico"
                className="text-2xl font-semibold tracking-tight"
                delay={32}
                duration={0.6}
                splitType="chars"
              />
              <div className="max-w-lg mx-auto">
                <BlurText
                  text="Giao diện kiểm thử trực tiếp cho Retrieval Graph và dữ liệu phòng thời gian thực. Tri thức khách sạn được đồng bộ tự động từ kho tri thức nội bộ."
                  className="font-medium text-muted-foreground text-sm leading-relaxed"
                  delay={35}
                />
              </div>
            </div>
          </div>
          <ExamplePrompts onPromptSelect={setInput} />
        </>
      ) : (
        <div className="w-full space-y-4 mb-20">
          {messages.map((message, i) => (
            <ChatMessage key={i} message={message} />
          ))}
          <div ref={messagesEndRef} />
        </div>
      )}

      <div className="fixed bottom-0 left-0 right-0 p-4 bg-background">
        <div className="max-w-5xl mx-auto space-y-4">
          <form onSubmit={handleSubmit} className="relative">
            <div className="flex gap-2 border rounded-md overflow-hidden bg-gray-50 items-center pr-1">
              <Input
                value={input}
                onChange={(e) => setInput(e.target.value)}
                placeholder="Nhập câu hỏi kiểm thử Pico..."
                className="border-0 focus-visible:ring-0 focus-visible:ring-offset-0 h-12 bg-transparent px-4"
                disabled={isLoading || !threadId}
              />
              <Magnet padding={24} magnetStrength={4}>
                <Button
                  type="submit"
                  size="icon"
                  className="rounded-md h-10 w-10"
                  disabled={!input.trim() || isLoading || !threadId}
                >
                  {isLoading ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                  ) : (
                    <ArrowUp className="h-4 w-4" />
                  )}
                </Button>
              </Magnet>
            </div>
          </form>
        </div>
      </div>
    </main>
  );
}
