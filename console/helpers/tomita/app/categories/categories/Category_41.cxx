#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 41 - Вывоз грунта, земли

Transp -> AnyWord<kwtype="утилизировать">;
What -> "грунт" | "асфальт" | "земля";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_41);