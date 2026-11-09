import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { fetchMessages, markMessagesRead } from "@/lib/api";

/** 消息中心：列表查询与全部已读。 */
export function useMessages() {
  const queryClient = useQueryClient();

  const listQuery = useQuery({
    queryKey: ["messages"],
    queryFn: fetchMessages,
  });

  const readMutation = useMutation({
    mutationFn: markMessagesRead,
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["messages"] });
      await queryClient.invalidateQueries({ queryKey: ["message-unread"] });
    },
  });

  const messages = listQuery.data ?? [];

  return {
    messages,
    unreadCount: messages.filter((m) => !m.is_read).length,
    isLoading: listQuery.isLoading,
    isError: listQuery.isError,
    error: listQuery.error,
    refetch: listQuery.refetch,
    readMutation,
  };
}
