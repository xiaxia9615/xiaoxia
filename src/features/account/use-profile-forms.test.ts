import { renderHook, act } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useProfileForms } from './use-profile-forms';

const spies = vi.hoisted(() => {
  const baseUser = {
    id: 1,
    name: '旧昵称',
    nickname: '旧昵称',
    email: 'user@example.com',
    sex: 1,
    birthday: '2000-01-01',
    phone: '13800000000',
    avatar: 'https://xiaoxia.ren/avatar.png',
    is_admin: false,
    is_finance: false,
    is_active: true,
  };
  return {
    baseUser,
    updateUserProfile: vi.fn(),
    changePassword: vi.fn(),
    updateUser: vi.fn(),
    logout: vi.fn(),
    toastSuccess: vi.fn(),
    toastError: vi.fn(),
  };
});

vi.mock('@/lib/api', () => ({
  AUTH_FEATURES: { profileEdit: true, changePassword: true, resetPassword: true },
}));

vi.mock('@/lib/api/user', () => ({
  updateUserProfile: spies.updateUserProfile,
  changePassword: spies.changePassword,
}));

vi.mock('./auth-provider', () => ({
  useAuth: () => ({
    user: spies.baseUser,
    updateUser: spies.updateUser,
    logout: spies.logout,
  }),
}));

vi.mock('sonner', () => ({
  toast: { success: spies.toastSuccess, error: spies.toastError },
}));

describe('useProfileForms 回归测试', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    spies.updateUserProfile.mockResolvedValue({ id: 1, name: '新昵称', phone: null, avatar: null });
    spies.changePassword.mockResolvedValue(undefined);
  });

  it('保存资料只提交表单字段，不再清空 phone/avatar', async () => {
    const { result } = renderHook(() => useProfileForms());
    act(() => {
      result.current.infoForm.setValue('nickname', '新昵称');
    });
    await act(async () => {
      await result.current.onSubmitProfile();
    });
    expect(spies.updateUserProfile).toHaveBeenCalledTimes(1);
    const payload: unknown = spies.updateUserProfile.mock.calls[0][0];
    expect(payload).toMatchObject({ name: '新昵称', sex: 1, birthday: '2000-01-01' });
    expect(payload).not.toHaveProperty('phone');
    expect(payload).not.toHaveProperty('avatar');
  });

  it('保存成功后同步全局用户态并保留原有字段', async () => {
    const { result } = renderHook(() => useProfileForms());
    act(() => {
      result.current.infoForm.setValue('nickname', '新昵称');
    });
    await act(async () => {
      await result.current.onSubmitProfile();
    });
    expect(spies.updateUser).toHaveBeenCalledTimes(1);
    expect(spies.updateUser).toHaveBeenCalledWith(
      expect.objectContaining({
        name: '新昵称',
        nickname: '新昵称',
        sex: 1,
        birthday: '2000-01-01',
        phone: '13800000000',
        avatar: 'https://xiaoxia.ren/avatar.png',
      }),
    );
    expect(spies.toastSuccess).toHaveBeenCalledWith('资料保存成功');
  });

  it('保存失败时不同步用户态并提示错误', async () => {
    spies.updateUserProfile.mockRejectedValueOnce(new Error('保存失败啦'));
    const { result } = renderHook(() => useProfileForms());
    await act(async () => {
      await result.current.onSubmitProfile();
    });
    expect(spies.updateUser).not.toHaveBeenCalled();
    expect(spies.toastError).toHaveBeenCalledWith('保存失败啦');
  });

  it('改密成功后登出，对齐后端撤销全部 token 的事实', async () => {
    const { result } = renderHook(() => useProfileForms());
    act(() => {
      result.current.passwordForm.setValue('oldPassword', 'old123');
      result.current.passwordForm.setValue('newPassword', 'new123456');
      result.current.passwordForm.setValue('confirm', 'new123456');
    });
    await act(async () => {
      await result.current.onSubmitPassword();
    });
    expect(spies.changePassword).toHaveBeenCalledWith('old123', 'new123456');
    expect(spies.logout).toHaveBeenCalledTimes(1);
    expect(spies.toastSuccess).toHaveBeenCalledWith('密码修改成功，请重新登录');
  });

  it('改密失败时保持登录态并提示错误', async () => {
    spies.changePassword.mockRejectedValueOnce(new Error('原密码不正确'));
    const { result } = renderHook(() => useProfileForms());
    act(() => {
      result.current.passwordForm.setValue('oldPassword', 'old123');
      result.current.passwordForm.setValue('newPassword', 'new123456');
      result.current.passwordForm.setValue('confirm', 'new123456');
    });
    await act(async () => {
      await result.current.onSubmitPassword();
    });
    expect(spies.logout).not.toHaveBeenCalled();
    expect(spies.toastError).toHaveBeenCalledWith('原密码不正确');
  });
});
