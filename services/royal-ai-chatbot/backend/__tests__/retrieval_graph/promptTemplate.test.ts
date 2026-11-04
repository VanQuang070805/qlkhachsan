import {
  ROUTER_SYSTEM_PROMPT,
  RESPONSE_SYSTEM_PROMPT,
} from '../../src/retrieval_graph/prompts.js';

describe('Prompt Templates', () => {
  describe('ROUTER_SYSTEM_PROMPT', () => {
    it('should format the router prompt correctly', async () => {
      const query = 'What is the capital of France?';
      const formattedPrompt = await ROUTER_SYSTEM_PROMPT.invoke({
        query,
      });

      expect(formattedPrompt.toString()).toContain('bộ phân luồng truy vấn');
      expect(formattedPrompt.toString()).toContain(query);
      expect(formattedPrompt.toString()).toContain("'retrieve'");
      expect(formattedPrompt.toString()).toContain("'direct'");
    });
  });

  describe('RESPONSE_SYSTEM_PROMPT', () => {
    it('should format the response prompt correctly', async () => {
      const context = 'Paris is the capital of France.';
      const toolContext = '<live_hotel_tools>{}</live_hotel_tools>';
      const question = 'Tell me about the capital of France.';

      const formattedPrompt = await RESPONSE_SYSTEM_PROMPT.invoke({
        context,
        toolContext,
        question,
      });

      expect(formattedPrompt.toString()).toContain('Rosaliza Concierge');
      expect(formattedPrompt.toString()).toContain(context);
      expect(formattedPrompt.toString()).toContain(toolContext);
      expect(formattedPrompt.toString()).toContain(question);
    });
  });
});
