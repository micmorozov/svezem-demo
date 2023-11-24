#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 99 - Аренда строительной техники

Transp -> AnyWord<kwtype="аренда">;
What -> "строительная" "техника";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_99);