import SpotlightCard from '@/components/reactbits/SpotlightCard';

interface ExamplePromptsProps {
  onPromptSelect: (prompt: string) => void;
}

const EXAMPLE_PROMPTS = [
  {
    tag: 'BẢNG GIÁ & HẠNG PHÒNG',
    title: 'Gợi ý hạng phòng cho 2 người và bảng giá hiện tại tại Rosaliza Hotel',
  },
  {
    tag: 'QUY ĐỊNH & TIỆN ÍCH',
    title: 'Khung giờ nhận/trả phòng và dịch vụ xe đưa đón sân bay Nội Bài',
  },
];

export function ExamplePrompts({ onPromptSelect }: ExamplePromptsProps) {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 w-full max-w-2xl">
      {EXAMPLE_PROMPTS.map((prompt, i) => (
        <SpotlightCard
          key={i}
          className="p-5 cursor-pointer hover:border-amber-700/40 transition-all"
        >
          <button
            type="button"
            className="w-full text-left space-y-1.5"
            onClick={() => onPromptSelect(prompt.title)}
          >
            <span className="text-[11px] font-semibold tracking-widest uppercase text-amber-800/80 block">
              {prompt.tag}
            </span>
            <p className="text-sm font-medium text-stone-800">{prompt.title}</p>
          </button>
        </SpotlightCard>
      ))}
    </div>
  );
}
