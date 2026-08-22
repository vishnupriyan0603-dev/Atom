using PersonalAIAssistant.Models;
using PersonalAIAssistant.Services;

namespace PersonalAIAssistant.ViewModels;

public sealed class ModelsViewModel(IAiProviderRegistry registry)
{
    public IReadOnlyList<AiModelInfo> CloudModels { get; } = registry.GetModels().Where(model => model.IsCloud).ToArray();
    public IReadOnlyList<AiModelInfo> LocalModels { get; } = registry.GetModels().Where(model => !model.IsCloud).ToArray();
}
